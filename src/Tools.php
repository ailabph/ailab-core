<?php

namespace Ailabph\AilabCore;

use Exception;
use Ailabph\AilabCore\UserI;

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
    static public function getCurrentUser(): UserI{
        if(!self::isLoggedIn()) Assert::throw("unable to retrieve current user, not logged in");
        return $GLOBALS["user"];
    }
    #endregion


    #region CHECKERS
    static public function isLoggedIn(): bool{
        return isset($GLOBALS["user"] )&& is_a($GLOBALS["user"],UserI::class);
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

            $logDateTime = TimeHelper::getCurrentTime()->format("Ymd").".log";
            $file_name .= $logDateTime;

            if(empty(self::$lastLogDate)){
                file_put_contents($file_name, self::LINE_SEPARATOR, FILE_APPEND);
                self::$lastLogDate = $logDateTime;
            }

            $formatted_message = TimeHelper::getCurrentTime()->format(TimeHelper::FORMAT_DATE_TIME_AMPM);
            $formatted_message = "[$formatted_message] ".$message;
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


}