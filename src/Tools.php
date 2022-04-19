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
}