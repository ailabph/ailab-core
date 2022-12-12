<?php

namespace Ailabph\AilabCore;

use Exception;

class Config
{

    public string $db_host = "";
    public string $db_name = "";
    public string $db_user = "";
    public string $db_pass = "";

    public string $site_url = "";
    public string $site_front_url = "";
    public string $site_domain = "";
    public string $theme_url = "";

    public string $site_name = "";
    public string $site_tagline = "";
    public string $site_prefix = "";
    public string $site_shortcode = "";
    public string $site_logo_box = "";
    public string $site_logo_wide = "";

    public bool $maintenance_mode = false;
    public string $maintenance_mode_message = "";
    public string $admin_ip = "";
    public bool $verbose_log = false;
    public bool $enable_twig_cache = false;
    public bool $force_stop_all_workers = false;

    public bool $enable_otp = true;


    /** ------------------------------------------------------------------------------------------------------------ */

    public const CONFIG_FILE = "config.ini.php";
    public const CONFIG_PUBLIC_FILE = "config_public.ini.php";

    /** ------------------------------------------------------------------------------------------------------------ */

    static string $OVERRIDE_PATH = "";

    static private Config|null $CONFIG;
    static private array|null $CONFIG_RAW;
    static private array|null $CONFIG_PUBLIC_RAW;

    const ENV = [
        "test" => "test",
        "local" => "local",
        "staging" => "staging",
        "latest" => "latest",
        "live" => "live",
    ];

    static public function getBaseDirectory(bool $force_real_path = false, bool $of_core_module = false): string
    {
        if(empty(self::$OVERRIDE_PATH) || $force_real_path){
            $path = dirname( __FILE__ );
            if($of_core_module){
                $to_split = "/src";
            }
            else{
                $to_split = str_contains($path, "/vendor/") ? "/vendor/" : "/src";
            }

            $path_split = explode($to_split,$path);
            return $path_split[0];
        }
        else{
            return self::$OVERRIDE_PATH;
        }
    }

    static public function resetCache(){
        self::$CONFIG = null;
        self::$CONFIG_RAW = null;
        self::$CONFIG_PUBLIC_RAW = null;
    }

    static private string $OVERRIDE_ENV = "";

    /**
     * @throws Exception
     */
    static public function overrideEnv(string $env){
        if(!in_array($env,self::ENV)){
            Assert::throw("Env:$env is not valid");
        }
        self::$OVERRIDE_ENV = $env;
    }

    static public function resetOverrideEnv(){
        self::$OVERRIDE_ENV = "";
    }

    /**
     * @return string
     */
    static public function getEnv() : string{
        if(!empty(self::$OVERRIDE_ENV)){
            return self::$OVERRIDE_ENV;
        }
        if(
            !isset($_SERVER["REMOTE_ADDR"]) ||
            (   str_contains($_SERVER["REMOTE_ADDR"], "127.0.0.1")
                || str_contains($_SERVER["REMOTE_ADDR"], "localhost")
            )
        ){
            return self::ENV["local"];
        }
        else if(
            str_contains(strtolower(self::getScriptDir()), "latest")
        ){
            return self::ENV["latest"];
        }
        else if(
            str_contains(strtolower(self::getScriptDir()), "staging")
            || str_contains(strtolower(self::getScriptDir()), "stage")
        ){
            return self::ENV["staging"];
        }
        else{
            return self::ENV["live"];
        }
    }

    static public string $CURRENT_DIR = __DIR__;
    static public function getScriptDir(): string{
        return static::$CURRENT_DIR;
    }

    /**
     * @throws Exception
     */
    static private function getIniFile(string $file_name = ""): bool|array
    {
        if(empty($file_name)){
            $file_name = static::CONFIG_FILE;
        }
        $path_to_ini = static::getBaseDirectory() . "/" . $file_name;
        if(!file_exists($path_to_ini)){
            ini_set("log_errors", 1);
            ini_set("error_log", "php-error.log");
            error_log("FATAL ERROR: Unable to find config file in $path_to_ini");
            Assert::throw("Something went wrong, please contact the admin staff");
        }
        return parse_ini_file($path_to_ini,true);
    }

    /**
     * @throws Exception
     */
    static private function init(bool $force_refresh = false){
        if(!isset(self::$CONFIG) || $force_refresh){
            self::$CONFIG = new config();

            // first load all values
            self::$CONFIG_RAW = self::getIniFile();
            foreach (self::$CONFIG_RAW as $key => $value) {
                if(property_exists(self::$CONFIG,$key)){
                    self::$CONFIG->{$key} = $value;
                }
            }

            // then load environment specific value
            if(isset(self::$CONFIG_RAW[self::getEnv()])){
                foreach(self::$CONFIG_RAW[self::getEnv()] as $key => $value){
                    if(property_exists(self::$CONFIG,$key)){
                        self::$CONFIG->{$key} = $value;
                    }
                }
            }

            self::checkCriticalConfigValues();
        }

        if(!isset(self::$CONFIG_PUBLIC_RAW) || $force_refresh){
            self::$CONFIG_PUBLIC_RAW = self::getIniFile(self::CONFIG_PUBLIC_FILE);
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    static private function checkCriticalConfigValues(): bool
    {
        if(!isset(self::$CONFIG)){
            Assert::throw("Config file is not yet initiated");
        }

        // check 1: check prepend db_ values if empty
        $is_empty = [];
        foreach(self::$CONFIG as $key => $value){
            if(strpos($key,"db_") !== false){
                if(empty($value)){
                    $is_empty[] = $key;
                }
            }
        }
        if(count($is_empty) > 0){
            $is_empty = implode(", ",$is_empty);
            Assert::throw("Following config properties must not be empty: $is_empty");
        }

        // check 2: enforce staging env using staging db
        if(self::getEnv() == self::ENV["staging"]){
            $db_arr = explode("_",self::getConfig()->db_name);
            if(!in_array("staging",$db_arr)){
                Assert::throw("On staging environment, expected to use staging database");
            }
        }

        return true;
    }

    /**
     * @param bool $force_refresh
     * @return config
     * @throws Exception
     */
    static public function getConfig(bool $force_refresh = false): Config
    {
        self::init($force_refresh);
        return self::$CONFIG;
    }

    static public function getPublicConfig(string $configClass = ""): ConfigPublic{
        $AppDir = "App\DBClassGenerator\DB\\";
        $configClass = empty($configClass) ? $configClass : $AppDir . $configClass;
        $config_public = class_exists($configClass) ? new $configClass() : new ConfigPublic();
        foreach (Config::$CONFIG_PUBLIC_RAW as $property => $value){
            $config_public->{$property} = $value;
        }
        return $config_public;
    }

    /**
     * @throws Exception
     */
    static public function getCustomOption(string $option_name, bool $must_have_value = false){
        self::init();
        $option_value = self::$CONFIG_RAW[$option_name] ?? "";
        if(isset(self::$CONFIG_RAW[self::getEnv()]) && isset( self::$CONFIG_RAW[self::getEnv()][$option_name] )){
            $option_value = self::$CONFIG_RAW[self::getEnv()][$option_name];
        }
        if(empty($option_value) && $must_have_value){
            Assert::throw("$option_name config is required");
        }
        return $option_value;
    }

    /**
     * @param string $option_name
     * @param bool $must_have_value
     * @return string
     * @throws Exception
     */
    static public function getPublicOption(string $option_name, bool $must_have_value = false, bool $force_refresh = false){
        self::init($force_refresh);
        if(!isset(self::$CONFIG_PUBLIC_RAW[$option_name]) && $must_have_value){
            Assert::throw("$option_name property does not exist on public config");
        }
        $option_value = self::$CONFIG_PUBLIC_RAW[$option_name] ?? "";
        if(isset(self::$CONFIG_PUBLIC_RAW[self::getEnv()])){
            if(isset(self::$CONFIG_PUBLIC_RAW[self::getEnv()][$option_name])){
                $option_value = self::$CONFIG_PUBLIC_RAW[self::getEnv()][$option_name];
                $option_value = self::$CONFIG_PUBLIC_RAW[self::getEnv()][$option_name];
            }
        }
        return $option_value;
    }


}