<?php

namespace Ailabph\AilabCore;

use Exception;

class Assert
{
    const IS_NOT_NUMERIC_MSG = "value is not numeric";

    /**
     * @throws Exception
     */
    static public function isNumeric(string $number, string $context = "", string $error_tag = ""): bool{
        if(!is_numeric($number)){
            self::throw(self::IS_NOT_NUMERIC_MSG,$context,$error_tag);
        }
        return true;
    }

    const IS_GREATER_THAN_MSG = "value is not greater than ";

    /**
     * @throws Exception
     */
    static public function isGreaterThan(string $number, string $value_to_compare = "0", string $context = "", string $error_tag = ""): bool{
        self::isNumeric($number,$context,$error_tag);
        self::isNumeric($value_to_compare,"(value_to_compare) ".$context,$error_tag);
        if(!($number > $value_to_compare)){
            self::throw(self::IS_GREATER_THAN_MSG."$value_to_compare",$context,$error_tag);
        }
        return true;
    }


    const IS_NOT_EMPTY_MSG = "value is empty";

    /**
     * @throws Exception
     */
    static public function isNotEmpty($value, string $context = "", string $error_tag = ""): bool{
        if(empty($value)){
            self::throw(self::IS_NOT_EMPTY_MSG, $context, $error_tag);
        }
        return true;
    }

    // ------------------------------------------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    static public function throw(string $error_message, string $context = "", string $error_tag = "", bool $critical_error = false){
        self::isNotEmpty($error_message,$context,$error_tag);

        if($critical_error){
            Tools::log(message:$error_message,category:"system",force_write: true,print_trace: true);
            $error_message = "System Error";
        }

        $formatted_msg = empty($context) ? $error_message : "Invalid $context, $error_message";
        $formatted_msg = empty($error_tag) ? $formatted_msg : "[$error_tag] $formatted_msg";

        throw new Exception($formatted_msg);
    }

    static public function isCallable(string $method, bool $throw = true): bool{
        if(empty($method)) return false;
        $is_callable = is_callable($method);
        if($is_callable) return true;
        if($throw){
            Assert::throw("$method is not callable");
        }
        return false;
    }

    //region CONNECTION RELATED
    public static function inTransaction()
    {
        if(!Connection::getConnection()->inTransaction()){
            self::throw("Must be in transaction mode");
        }
    }
    #endregion

    #region FILE RELATED
    public static function isPhpScript(string $script_file, bool $throw = true):bool{
        $has_php_extension = str_contains($script_file,".php");
        if(!$has_php_extension && $throw) Assert::throw("script:$script_file is not a valid php file");
        return $has_php_extension;
    }
    public static function isPhpScriptAndExist(string $script_file, bool $throw = true):bool{
        $is_php_file = self::isPhpScript(script_file:$script_file,throw:$throw);
        if(!file_exists($script_file) && $throw) Assert::throw("script:$script_file file does not exist");
        return true;
    }
    #endregion


    public static function isJsonString(string $data, bool $throw = true): bool{
        json_decode($data);
        if(json_last_error() != JSON_ERROR_NONE){
            if($throw){
                assert::throw("not a valid json format");
            }
            else{
                return false;
            }
        }
        return true;
    }

    public static function recordExist(TableClass $dataObj){
        if($dataObj->isNew()) Assert::throw(error_message:"record state is new",critical_error: true);
    }

    public static function isValidDate(string $date, bool $throw = true):bool {
        $time = strtotime($date);
        if(!$time){
            if($throw) Assert::throw("invalid date");
            return false;
        }
        return true;
    }
}