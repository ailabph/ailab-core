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
    static public function throw(string $error_message, string $context = "", string $error_tag = ""){
        self::isNotEmpty($error_message,$context,$error_tag);

        $formatted_msg = empty($context) ? $error_message : "Invalid $context, $error_message";
        $formatted_msg = empty($error_tag) ? $formatted_msg : "[$error_tag] $formatted_msg";

        throw new Exception($formatted_msg);
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
}