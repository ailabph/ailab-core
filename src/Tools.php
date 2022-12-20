<?php

namespace Ailabph\AilabCore;

use Exception;
use Ailabph\AilabCore\UserI;
use App\DBClassGenerator\DB;

class Tools
{
    const LINE_SEPARATOR = "--------------------------------------------------------------------------";

    #region GETTERS
    public static function getUserAgent(): string{
        return $_SERVER["HTTP_USER_AGENT"] ?? "";
    }

    public static function getFingerprint(): string{
        return $_COOKIE["fp"] ?? "";
    }

    public static function getSession(): string{
        return $_COOKIE["session"] ?? "";
    }

    /**
     * @throws Exception
     */
    static public function getCurrentUser(bool $throw = true): DB\userX{
        if(!self::isLoggedIn()) Assert::throw("unable to retrieve current user, not logged in");
        return $GLOBALS["user"];
    }

    public static function getIpAddress(){
        if( array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')>0) {
                $addr = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($addr[0]);
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        else {
            return $_SERVER['REMOTE_ADDR'] ?? "";
        }
    }

    const STRING = "string";
    const FLOAT = "float";
    const INT = "int";
    const BOOLEAN = "boolean";

    public static function getPhpTypeFromSqlType(string $sql_type): string{
        Assert::isNotEmpty($sql_type,"sql_type");
        $sql_type_parts = explode("(",$sql_type);
        $base_sql_type = strtolower( $sql_type_parts[0] );

        $php_type = "";

        $int_types = [
            "tinyint",
            "smallint",
            "mediumint",
            "int",
            "bigint",
            "bit",
            "serial",
            "timestamp",
        ];
        if(in_array($base_sql_type,$int_types)) $php_type = self::INT;

        if($base_sql_type == "boolean") $php_type = self::BOOLEAN;

        $float_types = [
            "decimal",
            "float",
            "double",
            "real"
        ];
        if(in_array($base_sql_type,$float_types)) $php_type = self::FLOAT;

        $string_types = [
            "date",
            "datetime",
            "time",
            "year",
            "char",
            "varchar",
            "tinytext",
            "text",
            "mediumtext",
            "longtext",
            "binary",
            "varbinary",
            "tinyblob",
            "blob",
            "mediumblob",
            "longblob",
            "json",
        ];
        if(in_array($base_sql_type,$string_types)) $php_type = self::STRING;

        if(empty($php_type)){
            Assert::throw("sql type $base_sql_type not yet assigned to a php type");
        }

        return $php_type;
    }

    /**
     * @throws Exception
     */
    static public function getValueFromArray(string $property, array $source, bool $strict = false, string $error_tag = ""){
        if(isset($source[$property])){
            return $source[$property];
        }
        if($strict){
            Assert::throw("property:$property not found","",$error_tag);
        }
        return null;
    }

    static public function getDefaultPageParam(): array{
        $param["MAINTENANCE_MODE"] = Config::getConfig()->maintenance_mode;
        $param["maintenance_mode"] = Config::getConfig()->maintenance_mode;

        $param["MAINTENANCE_MESSAGE"] = Config::getConfig()->maintenance_mode_message;
        $param["maintenance_message"] = Config::getConfig()->maintenance_mode_message;

        $param["user"] = Tools::isLoggedIn() ? Tools::getCurrentUser() : null;

        $param["ROOTDIRECTORY"] = Config::getBaseDirectory();
        $param["base_dir"] = Config::getBaseDirectory();

        $param["module_dir"] = Config::getBaseDirectory(of_core_module: true);

        if(self::isInModule()){
            $param["core_script_url"] = Config::getConfig()->site_url . "/vendor/ailabph/ailab-core/core_scripts";
        }
        else{
            $param["core_script_url"] = Config::getConfig()->site_url . "/core_scripts";
        }

        $param["page"] = Render::getPage();
        $param["page_details"] = Render::getPageDetails();
        $param["page_description"] = Render::getPageDescription();

        $param["SITE_NAME"] = Config::getPublicConfig()->site_name;
        $param["site_name"] = Config::getPublicConfig()->site_name;

        $param["SENDERNAME"] = Config::getPublicConfig()->site_shortcode;

        $param["URL"] = Config::getConfig()->site_url;
        $param["APP_URL"] = config::getConfig()->site_url;
        $param["url"] = Config::getConfig()->site_url;

        $param["SITE_URL"] = Config::getConfig()->site_front_url;
        $param["SITE_URL_SHORT"] = Config::getConfig()->site_front_url;
        $param["site_url"] = Config::getConfig()->site_front_url;


        $param["APP_URL_SHORT"] = Config::getConfig()->site_domain;
        $param["site_domain"] = Config::getConfig()->site_domain;

        $param["THEME_URL"] = Config::getPublicConfig()->theme_url;
        $param["theme_url"] = Config::getPublicConfig()->theme_url;

        $param["SITE_PREFIX"] = Config::getPublicConfig()->site_prefix;
        $param["site_prefix"] = Config::getPublicConfig()->site_prefix;

        $param["ENABLE_OTP"] = Config::getConfig()->enable_otp;
        $param["enable_otp"] = Config::getConfig()->enable_otp;

        $param["LOGIN_BG"] = Config::getPublicConfig()->login_bg;
        $param["login_bg"] = Config::getPublicConfig()->login_bg;

        $param["LOGIN_LOGO"] = Config::getPublicConfig()->logo_login;
        $param["logo_login"] = Config::getPublicConfig()->logo_login;

        $param["LOGO"] = Config::getPublicConfig()->logo_wide;
        $param["logo_box"] = Config::getPublicConfig()->logo_box;
        $param["logo_wide"] = Config::getPublicConfig()->logo_wide;

        $param["STRUCTURE_BG"] = Config::getPublicConfig()->structure_bg;
        $param["structure_bg"] = Config::getPublicConfig()->structure_bg;

        $param["LOGIN_BG"] = Config::getPublicConfig()->login_bg;
        $param["login_bg"] = Config::getPublicConfig()->login_bg;

        return $param;
    }

    public static function getAppClassNamespace(string $class): string
    {
        if(str_contains($class,TableClass::BASE_NAME_SPACE)) return $class;
        return TableClass::BASE_NAME_SPACE."\\".$class;
    }

    #endregion END GETTERS


    #region CHECKERS

    static public function isLoggedIn(): bool{
        return Session::getCurrentUser(throw:false) instanceof DB\user;
    }

    static public function isInModule(): bool{
        return str_contains(__DIR__,"/vendor/");
    }

    static public function isActiveMember(): bool{
        if(!self::isLoggedIn()) return false;
        return Tools::getCurrentUser()->usergroup != "new";
    }

    static public function isStaff(): bool{
        if(!self::isLoggedIn()) return false;
        return in_array(Tools::getCurrentUser()->usergroup,["admin","owner","staff","approver","reviewer"]);
    }

    static public function isCenter(): bool{
        if(!self::isLoggedIn()) return false;
        return in_array(Tools::getCurrentUser()->usergroup,["mobile_stockist","municipal","provincial","regional","country"]);
    }

    public static function checkPropertiesExistInClass(string $class, array $properties) : bool
    {
        if(!class_exists($class)) Assert::throw("class: $class does not exist");
        foreach ($properties as $property) {
            if(str_contains($property,"::")){
                if(!defined($class.$property)){
                    Assert::throw("constant $property does not exist in $class");
                }
            }
            else{
                if(!property_exists($class,$property)){
                    Assert::throw("property:$property does not exist in $class");
                }
            }

        }
        return true;
    }

    public static function appClassExist(string $class, bool $throw = true): string|false{
        $class = self::getAppClassNamespace($class);
        if(!class_exists($class)){
            if($throw) Assert::throw("class:$class does not exist");
            return false;
        }
        if(!class_exists($class)) Assert::throw("class:$class does not exist");
        return $class;
    }

    #endregion END CHECKERS


    #region LOGGERS
    private static string $lastLogDate = "";

    public static function logPure(string $log, string $file_name = "logs.log"){
        $log_directory = Config::getBaseDirectory()."/logs";
        $full_path = $log_directory . "/" . $file_name;
        file_put_contents($full_path, $log.PHP_EOL, FILE_APPEND);
        if($file_name != "logs.log" && (Config::getEnv() == Config::ENV["test"] || Config::getEnv() == Config::ENV["local"])){
            self::logPure($log);
        }
    }

    /**
     * @throws Exception
     */
    public static function log(string $message, string $category = "", bool $force_write = false, bool $print_trace = false): void{
        if(Config::getConfig()->verbose_log || $force_write){
            Assert::isNotEmpty($message,"log message");

            $file_name = "log_";
            if(!empty($category)){
                $file_name .= $category ."_";
            }

            $file_name .= TimeHelper::getCurrentTime()->format("Ymd").".log";
            $formatted_message = "";
            $logDateTime = TimeHelper::getCurrentTime()->format(TimeHelper::FORMAT_DATE_TIME_AMPM);

            if(self::$lastLogDate != $logDateTime){
                $formatted_message .= "[".$logDateTime."] ";
            }

            if(empty(self::$lastLogDate)){
                self::logPure(log:self::LINE_SEPARATOR,file_name: $file_name);
                self::$lastLogDate = $logDateTime;
            }

            $formatted_message .= $message;
            self::logPure(log:$formatted_message,file_name: $file_name);
            if($category != "query"){
                self::logPure(log:$formatted_message,file_name: "logs_no_query.log");
            }

            if($print_trace){
                try{
                    throw new Exception($message);
                }catch (Exception $e){
                    $trace = $e->getTraceAsString();
                    self::log(message:PHP_EOL.$trace,category:$category,force_write: $force_write);
                }
            }

            if(self::$lastLogDate != $logDateTime){
                file_put_contents($file_name, self::LINE_SEPARATOR, FILE_APPEND);
                self::$lastLogDate = $logDateTime;
            }

        }
    }

    /**
     * @throws Exception
     */
    public static function logIncident(string $message, string $category, bool $printLoggedDevice = true, bool $printStackTrace = true){
        $category = $category ."_incident";
        if($printLoggedDevice){
            $username = self::isLoggedIn() ? self::getCurrentUser()->username : "";
            $fp = self::getFingerprint();
            $ip = self::getIpAddress();
            $device = self::getUserAgent();
            $log = "user:$username | ip:$ip | device:$device | fp:$fp";
            self::log(message: $log,category: $category,force_write: true);
        }
        self::log(message: $message,category: $category,force_write: true,print_trace: $printStackTrace);
    }

    /**
     * @throws Exception
     */
    public static function writeDebug(string|array|object $log){
        if(!is_string($log)){
            $log = PHP_EOL.print_r($log,true);
        }
        self::log(message:$log,category: "debug",force_write: true,print_trace: false);
    }

    public static function emptyLogs(){
        // get a list of all files in the specified folder
        $logs_dir = Config::getBaseDirectory()."/logs/*";
        $files = glob($logs_dir);

        // iterate over the list of files
        foreach($files as $file) {
            // skip the index.php file
            if(str_contains($file,"index.php")){
                continue;
            }
            // delete the file
            unlink($file);
        }
    }

    #endregion END OF LOGGERS


    #region DATA PROCESS

    public static function convertArrayOfStringToString(array $array, string $separator, string $data_wrapper = "", bool $preserve_keys = false): string{
        Assert::isNotEmpty($separator,"array separator");
        $to_return = "";

        if($preserve_keys){
            foreach ($array as $property=>$value){
                if(!empty($to_return)) $to_return .= ", ";
                $to_return .= " ".$data_wrapper.$property.$data_wrapper." => ".$data_wrapper.$value.$data_wrapper;
            }
        }
        else{
            $new_data = [];
            foreach ($array as $property=>$value){
                if(!empty($data_wrapper)){
                    $value = $data_wrapper . $value . $data_wrapper;
                }
                $new_data[] = $value;
            }
            $to_return = implode($separator,$new_data);
        }
        return $to_return;
    }

    static public function importValuesToObject(
        array|object $from_data,
        object       &$to_object,
        string       $prefix = "",
        bool         $strict = false,
    ){
        foreach ($to_object as $property => $value){
            $extracted_value = null;
            if(is_array($from_data)){
                $extracted_value = self::getValueFromArray($property,$from_data,$strict);
            }
            else{
                if(!property_exists($from_data,$property)){
                    if($strict) Assert::throw("property$property does not exist on source data object");
                }
                else{
                    $extracted_value = $from_data->{$property};
                }
            }
            $property_with_prefix = $prefix . $property;
            if(!property_exists($to_object,$property_with_prefix)){
                if($strict) Assert::throw("unable to copy value to property:$property_with_prefix. property does not exist");
            }
            else{
                // suppress errors of incompatible types
                try{
                    $to_object->{$property_with_prefix} = is_null($extracted_value) && isset($value) ? $value : $extracted_value;
                }catch (\TypeError $e){}
            }
        }
    }

    public static function parseKeyArray(array $data, string $key, bool $throw = true){
        if(!isset($data[$key])){
            if($throw) Assert::throw("$key data is required");
            return false;
        }
        return $data[$key];
    }

    public static function parsePropertyFromObject(object $object, string $property, bool $throw = true): string|false{
        if(!property_exists($object,$property)){
            if($throw) Assert::throw("property $property is required");
            return false;
        }
        return $object->{$property};
    }

    public static function parseJson(string $data): object|array{
        Assert::isJsonString($data);
        return json_decode(htmlspecialchars_decode($data, ENT_QUOTES));
    }

    #endregion END OF DATA PROCESS


    #region TEMPLATES
    #endregion


}