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

        // TODO: Implement Log to File Here

        throw new Exception($formatted_msg);
    }
}