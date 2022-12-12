<?php

namespace Ailabph\AilabCore;

use Exception;
use Ailabph\AilabCore\UserI;
use App\DBClassGenerator\DB;

class Tools
{
    const LINE_SEPARATOR = PHP_EOL."--------------------------------------------------------------------------".PHP_EOL;

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
    static public function getCurrentUser(): DB\userX{
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
    #endregion


    #region CHECKERS
    static public function isLoggedIn(): bool{
        return isset($GLOBALS["user"]) && $GLOBALS["user"] instanceof DB\userX;
    }
    #endregion


    #region LOGGERS
    private static string $lastLogDate = "";

    /**
     * @throws Exception
     */
    public static function log(string $message, string $category = "", bool $force_write = false, bool $print_trace = false): void{
        if(Config::getConfig()->verbose_log || $force_write){
            Assert::isNotEmpty($message,"log message");

            $log_directory = Config::getBaseDirectory() . "/logs";
            $file_name = $log_directory . "/log_";
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
                file_put_contents($file_name, self::LINE_SEPARATOR, FILE_APPEND);
                self::$lastLogDate = $logDateTime;
            }

            $formatted_message .= $message;
            $formatted_message .= PHP_EOL;
            file_put_contents($file_name, $formatted_message, FILE_APPEND);

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
            $username = self::isLoggedIn() ? self::getCurrentUser()->getUsername() : "";
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
    #endregion END OF LOGGERS


    #region DATA PROCESS

    public static function convertArrayOfStringToString(array $array, string $separator, string $data_wrapper = ""): string{
        Assert::isNotEmpty($separator,"array separator");
        $new_data = [];
        foreach ($array as $item=>$value){
            if(!empty($data_wrapper)){
                $value = $data_wrapper . $value . $data_wrapper;
            }
            $new_data[] = $value;
        }
        return implode($separator,$new_data);
    }

    #endregion END OF DATA PROCESS


    #region TEMPLATES
    #endregion

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

    /**
     * @throws Exception
     */
    static public function importValuesFromArrayToObject(array $from_array, object &$to_object, bool $strict = false, string $error_tag = ""){
        foreach ($to_object as $property => $value){
            $extracted_value = self::getValueFromArray($property,$from_array,$strict,$error_tag);
            $to_object->{$property} = is_null($extracted_value) && isset($value) ? $value : $extracted_value;
        }
    }

    static public function isInModule(): bool{
        return str_contains(__DIR__,"/vendor/");
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




}