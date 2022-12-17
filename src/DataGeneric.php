<?php

namespace Ailabph\AilabCore;

class DataGeneric
{
    public static function get(string $base_class, string $extended_class, int|string|TableClass $dataObj, string $priKey, string $uniKey, bool $baseOnly = false, bool $throw = true){
        $data_name = $base_class;
        $base_class = Tools::appClassExist($base_class);
        $extended_class = Tools::appClassExist($extended_class);
        $class_to_use = $baseOnly ? $base_class : $extended_class;
        $to_return = new $class_to_use();
        $get_method = "";

        if($dataObj instanceof $class_to_use){
            $to_return = $dataObj;
            $get_method = ", via passed object";
        }
        else if($dataObj instanceof $base_class && !$baseOnly){
            $get_method = ", via conversion to $extended_class with id:".$dataObj->{$priKey};
            $to_return = new $class_to_use(["$priKey"=>$dataObj->{$priKey}]);
        }

        if($to_return->isNew() && is_numeric($dataObj)){
            $get_method = ", via id:$dataObj";
            $to_return = new $class_to_use(["$priKey"=>$dataObj]);
        }

        if($to_return->isNew() && is_string($dataObj)){
            $get_method = ", via $uniKey:$dataObj";
            $to_return = new $class_to_use(["$uniKey"=>$dataObj]);
        }

        if($to_return->isNew()){
            if($throw) Assert::throw("unable to retrieve record $data_name".$get_method);
            else return false;
        }
        return $to_return;
    }

    public static function getDataObjectsFromArray(array $args, string $classToFind): TableClass|false{
        $classToFind = Tools::appClassExist(class: $classToFind, throw:true);
        foreach ($args as $arg){
            if($arg instanceof $classToFind) return $arg;
        }
        return false;
    }
}