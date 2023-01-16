<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB\meta_options;
use App\DBClassGenerator\DB\meta_optionsList;

class Patcher implements Loggable
{
    public static string $patch_record_json_file = "patch_record.json";

    public static function runPatch(bool $regenerate_classes = false, bool $force_run = false){
        if(Config::getConfig()->verbose_log) self::addLog("executing patches, current_env:".Config::getEnv()." db:".Config::getConfig()->db_name,__LINE__);

        // if local, patch also the test environment
        if(Config::getEnv() == Config::ENV["local"]){
            if(Config::getConfig()->verbose_log) self::addLog("local env detected, attempting to run patches on both local and test",__LINE__);
            if(Config::getConfig()->verbose_log) self::addLog("setting env to test and running patch",__LINE__);
            Config::resetCache();
            Connection::reset();
            Config::resetOverrideEnv();
            Config::overrideEnv(Config::ENV["test"]);
            $site_patch_executed = self::runSiteLevelPatch($force_run);
            $core_patch_executed = self::runCorePatches($force_run);

            if(Config::getConfig()->verbose_log) self::addLog("setting env to local and running patch",__LINE__);
            Config::resetCache();
            Connection::reset();
            Config::resetOverrideEnv();
            Config::overrideEnv(Config::ENV["local"]);
        }
        $site_patch_executed = self::runSiteLevelPatch($force_run);
        $core_patch_executed = self::runCorePatches($force_run);
        if(Config::getConfig()->verbose_log) self::addLog("run patch done",__LINE__);

        if($site_patch_executed > 0 || $core_patch_executed > 0 || Config::getConfig()->verbose_log){
            self::addLog("site_patch_executed:$site_patch_executed",__LINE__);
            self::addLog("core_patch_executed:$core_patch_executed",__LINE__);
        }


        // if local, generate classes
        if(!$regenerate_classes){
            if(Config::getConfig()->verbose_log) self::addLog("skipping generation of db classes",__LINE__);
            return;
        }
        $is_local_or_test_env = Config::getEnv() == Config::ENV["local"] || Config::getEnv() == Config::ENV["test"];
        $has_patch_executed = $site_patch_executed > 0 || $core_patch_executed > 0;
        if($is_local_or_test_env && $has_patch_executed){
            self::addLog("local or test env detected, generating db class php",__LINE__);
            GeneratorClassPhp::run();
        }
        else{
            if(Config::getConfig()->verbose_log) self::addLog("env(".Config::getEnv().") not local or test, skipping db class generation",__LINE__);
        }
    }

    private static function runSiteLevelPatch(bool $force_run = false):int{
        $patch_executed = 0;
        $patch_record = self::getPatchJsonFile();
        $patch_dir = self::getPatchDirectory(of_core_module: false);

        if(Config::getConfig()->verbose_log) self::addLog("scanning patch folder and collecting patch files",__LINE__);
        $patches = [];
        $patch_dir_result = scandir($patch_dir);
        foreach($patch_dir_result as $file) {
            if(preg_match('/^patch_.*.php/', $file) && !is_dir(__DIR__ . $file)) {
                if(Config::getConfig()->verbose_log) self::addLog("patch file:$file",__LINE__);
                $patches[] = $file;
            }
        }

        foreach($patches as $file_name){
            $key = str_replace(".php","",$file_name);
            $key_env = Config::getEnv() . "_" .$key;
            $patch_path = $patch_dir . "/" . $file_name;

            if(Config::getConfig()->verbose_log) self::addLog("EXECUTING patch:$key | env:".Config::getEnv()." | db:".Config::getConfig()->db_name,__LINE__);
            if(isset($patch_record->{$key_env}) && !$force_run) {
                if(Config::getConfig()->verbose_log) self::addLog("skipping, patch already executed on this environment:".Config::getEnv()." key:$key_env",__LINE__);
                continue;
            }

            // suppress errors to continue running other patches
            try{
                self::addLog("running patch:{$file_name}",__LINE__);
                require($patch_path);
                $patch_record->{$key_env} = time();
                $patch_executed++;
                self::addLog("patch done",__LINE__);
            }catch (\Exception $e){
                self::addLog("error executing patch:$key error:".$e->getMessage(),__LINE__);
                Tools::logIncident(message:"patch failed for $key",category: "patch",printLoggedDevice: false,printStackTrace: false);
                Tools::logIncident(message:$e->getMessage(),category: "patch",printLoggedDevice: false,printStackTrace: false);
                Tools::logIncident(message:Tools::LINE_SEPARATOR,category:"patch",printLoggedDevice: false,printStackTrace: false);
            }
        }

        if(Config::getConfig()->verbose_log) self::addLog("updating patch json file",__LINE__);
        self::updatePatchJsonFile($patch_record);
        return $patch_executed;
    }

    private static function runCorePatches(bool $force_run = false):int{
        $patch_executed = 0;
        if(Config::getConfig()->verbose_log) self::addLog("running core patches",__LINE__);
        $patch_record = self::getPatchJsonFile();
        $patch_dir = self::getPatchDirectory(of_core_module: true);
        if(Config::getConfig()->verbose_log) self::addLog("patch dir:$patch_dir",__LINE__);
        if(Config::getConfig()->verbose_log) self::addLog("scanning patch folder and collecting patch files",__LINE__);
        $patches = [];
        $patch_dir_result = scandir($patch_dir);
        foreach($patch_dir_result as $file) {
            if(preg_match('/^core_.*.php/', $file) && !is_dir(__DIR__ . $file)) {
                if(Config::getConfig()->verbose_log) self::addLog("patch file:$file",__LINE__);
                $patches[] = $file;
            }
        }

        foreach($patches as $file_name){
            $key = str_replace(".php","",$file_name);
            $key_env = Config::getEnv() . "_" .$key;
            $patch_path = $patch_dir . "/" . $file_name;

            if(Config::getConfig()->verbose_log) self::addLog("EXECUTING patch:$key | env:".Config::getEnv()." | db:".Config::getConfig()->db_name,__LINE__);
            if(isset($patch_record->{$key_env}) && !$force_run) {
                if(Config::getConfig()->verbose_log) self::addLog("skipping, patch already executed",__LINE__);
                continue;
            }

            // suppress errors to continue running other patches
            try{
                self::addLog("running patch:{$file_name}",__LINE__);
                require($patch_path);
                $patch_record->{$key_env} = time();
                $patch_executed++;
                self::addLog("patch done",__LINE__);
            }catch (\Exception $e){
                self::addLog("error executing patch:$key error:".$e->getMessage(),__LINE__);
                Tools::logIncident(message:"patch failed for $key",category: "patch",printLoggedDevice: false,printStackTrace: false);
                Tools::logIncident(message:$e->getMessage(),category: "patch",printLoggedDevice: false,printStackTrace: false);
                Tools::logIncident(message:Tools::LINE_SEPARATOR,category:"patch",printLoggedDevice: false,printStackTrace: false);
            }
        }

        self::updatePatchJsonFile($patch_record);
        return $patch_executed;
    }

    private static function getPatchDirectory(bool $of_core_module = false): string{
        return Config::getBaseDirectory(of_core_module: $of_core_module) . "/patch";
    }

    private static function getPatchJsonFile(): object{
        $patch_dir = self::getPatchDirectory(of_core_module: false);
        $patch_json_file_path = $patch_dir . "/" . self::$patch_record_json_file;
        if(!is_file($patch_json_file_path)){
            if(Config::getConfig()->verbose_log) self::addLog("patch record json file not found, creating file",__LINE__);
            file_put_contents($patch_json_file_path,"{}");
        }


        if(Config::getConfig()->verbose_log) self::addLog("opening patch record json file",__LINE__);
        $patch_record = json_decode(file_get_contents($patch_json_file_path,true));
        if(!is_object($patch_record)) Assert::throw("patch record json not valid");
        if(Config::getConfig()->verbose_log) self::addLog($patch_record,__LINE__);

        return $patch_record;
    }

    private static function updatePatchJsonFile(object $patch_record){
        $patch_dir = self::getPatchDirectory(of_core_module: false);
        $patch_json_file_path = $patch_dir . "/" . self::$patch_record_json_file;
        file_put_contents($patch_json_file_path,json_encode($patch_record,JSON_PRETTY_PRINT));
    }

    static function addLog(string|array|object $log, int $line)
    {
        Logger::add(msg:$log,category: "patch",line:$line,always_write: true);
    }
}