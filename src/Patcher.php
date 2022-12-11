<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB\meta_options;
use App\DBClassGenerator\DB\meta_optionsList;

class Patcher implements Loggable
{
    public static string $patch_record_json_file = "patch_record.json";

    public static function runPatch(bool $regenerate_classes = false){
        self::runSiteLevelPatch($regenerate_classes);
        self::runCorePatches($regenerate_classes);
    }

    public static function runSiteLevelPatch(bool $regenerate_classes = true){
        $patch_record = self::getPatchJsonFile();
        $patch_dir = self::getPatchDirectory(of_core_module: false);

        self::addLog("scanning patch folder and collecting patch files",__LINE__);
        $patches = [];
        $patch_dir_result = scandir($patch_dir);
        foreach($patch_dir_result as $file) {
            if(preg_match('/^patch_.*.php/', $file) && !is_dir(__DIR__ . $file)) {
                self::addLog("patch file:$file",__LINE__);
                $patches[] = $file;
            }
        }

        foreach($patches as $file_name){
            $key = str_replace(".php","",$file_name);
            $patch_path = $patch_dir . "/" . $file_name;

            self::addLog("executing patch:$key",__LINE__);
            if(isset($patch_record->{$key})) {
                self::addLog("skipping, patch already executed",__LINE__);
                continue;
            }

            // suppress errors to continue running other patches
            try{
                self::addLog("running patch",__LINE__);
                require_once($patch_path);
                $patch_record->{$key} = time();
                self::addLog("patch done",__LINE__);
            }catch (\Exception $e){
                self::addLog("error executing patch:$key error:".$e->getMessage(),__LINE__);
                Tools::logIncident(message:"patch failed for $key",category: "patch",printLoggedDevice: false,printStackTrace: false);
                Tools::logIncident(message:$e->getMessage(),category: "patch",printLoggedDevice: false,printStackTrace: false);
                Tools::logIncident(message:Tools::LINE_SEPARATOR,category:"patch",printLoggedDevice: false,printStackTrace: false);
            }
        }

        self::addLog("updating patch json file",__LINE__);
        self::updatePatchJsonFile($patch_record);

        // if local, generate classes
        if(!$regenerate_classes){
            self::addLog("skipping generation of db classes",__LINE__);
            return;
        }
        if(Config::getEnv() == Config::ENV["local"]){
            self::addLog("local env detected, generating db class php",__LINE__);
            GeneratorClassPhp::run();
        }
        else{
            self::addLog("env(".Config::getEnv().") not local, skipping db class generation",__LINE__);
        }
    }

    static function runCorePatches(bool $generate_classes = false){
        self::addLog("running core patches",__LINE__);
        $patch_record = self::getPatchJsonFile();
        $patch_dir = self::getPatchDirectory(of_core_module: true);
        self::addLog("patch dir:$patch_dir",__LINE__);
        self::addLog("scanning patch folder and collecting patch files",__LINE__);
        $patches = [];
        $patch_dir_result = scandir($patch_dir);
        foreach($patch_dir_result as $file) {
            if(preg_match('/^core_.*.php/', $file) && !is_dir(__DIR__ . $file)) {
                self::addLog("patch file:$file",__LINE__);
                $patches[] = $file;
            }
        }

        foreach($patches as $file_name){
            $key = str_replace(".php","",$file_name);
            $patch_path = $patch_dir . "/" . $file_name;

            self::addLog("executing patch:$key",__LINE__);
            if(isset($patch_record->{$key})) {
                self::addLog("skipping, patch already executed",__LINE__);
                continue;
            }

            // suppress errors to continue running other patches
            try{
                self::addLog("running patch",__LINE__);
                require_once($patch_path);
                $patch_record->{$key} = time();
                self::addLog("patch done",__LINE__);
            }catch (\Exception $e){
                self::addLog("error executing patch:$key error:".$e->getMessage(),__LINE__);
                Tools::logIncident(message:"patch failed for $key",category: "patch",printLoggedDevice: false,printStackTrace: false);
                Tools::logIncident(message:$e->getMessage(),category: "patch",printLoggedDevice: false,printStackTrace: false);
                Tools::logIncident(message:Tools::LINE_SEPARATOR,category:"patch",printLoggedDevice: false,printStackTrace: false);
            }
        }

        self::updatePatchJsonFile($patch_record);
    }

    private static function getPatchDirectory(bool $of_core_module = false): string{
        return Config::getBaseDirectory(of_core_module: $of_core_module) . "/patch";
    }

    private static function getPatchJsonFile(): object{
        $patch_dir = self::getPatchDirectory(of_core_module: false);
        $patch_json_file_path = $patch_dir . "/" . self::$patch_record_json_file;
        if(!is_file($patch_json_file_path)){
            self::addLog("patch record json file not found, creating file",__LINE__);
            file_put_contents($patch_json_file_path,"{}");
        }


        self::addLog("opening patch record json file",__LINE__);
        $patch_record = json_decode(file_get_contents($patch_json_file_path,true));
        if(!is_object($patch_record)) Assert::throw("patch record json not valid");
        self::addLog($patch_record,__LINE__);

        return $patch_record;
    }

    private static function updatePatchJsonFile(object $patch_record){
        $patch_dir = self::getPatchDirectory(of_core_module: false);
        $patch_json_file_path = $patch_dir . "/" . self::$patch_record_json_file;
        file_put_contents($patch_json_file_path,json_encode($patch_record));
    }

    static function addLog(string|array|object $log, int $line)
    {
        Logger::add(msg:$log,category: "patch",line:$line);
    }
}