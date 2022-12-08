<?php

namespace Ailabph\AilabCore;

use Exception;

class Tools
{
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

            $formatted_message = TimeHelper::getCurrentTime()->format(TimeHelper::FORMAT_DATE_TIME_AMPM);
            $formatted_message = "[$formatted_message] ".$message;
            $formatted_message .= PHP_EOL;
            file_put_contents($file_name, $formatted_message, FILE_APPEND);

            if($print_trace){
                try{
                    throw new Exception($message);
                }catch (Exception $e){
                    $trace = $e->getTraceAsString();
                    self::log(message:$trace,category:$category,force_write: $force_write);
                }
            }
        }
    }
}