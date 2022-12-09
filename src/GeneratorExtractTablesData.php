<?php

namespace Ailabph\AilabCore;
use PDO;

class GeneratorExtractTablesData
{
    private static \PDO $connection;

    /** @return TableDataHeader[] */
    public static function getTablesInfo(): array
    {
        self::$connection = Connection::getConnection();
        $tables = self::initiateAndRetrieveTableNames();
        foreach ($tables as $tableDataHeader){
            $tableDataHeader = self::retrieveTableProperties($tableDataHeader);
            foreach ($tableDataHeader->properties as $property){
                $tableDataHeader->data_properties[] = $property->field;
                $tableDataHeader->data_property_types[$property->field] = $property->type;

                if(
                    str_contains($property->type,"char")
                    || str_contains($property->type,"text")
                    || str_contains($property->type,"date")
                ){
                    $property->object_types = "string";
                }
                else if(
                    str_contains($property->type,"int")
                ){
                    $property->object_types = "int";
                }
                else if(
                    str_contains($property->type,"decimal")
                ){
                    $property->object_types = "float";
                }

                if(!empty($property->key)){
                    $tableDataHeader->dataKeys[] = $property->field;
                    if($property->key == "PRI"){
                        $tableDataHeader->dataKeysPrimary[] = $property->field;
                        $property->object_types .= "|null";
                    }
                    if($property->key == "UNI"){
                        $tableDataHeader->dataKeysUnique[] = $property->field;
                    }
                }
                if($property->extra == "auto_increment"){
                    $tableDataHeader->dataKeysAutoInc[] = $property->field;
                }
                if($property->null == "NO"){
                    $tableDataHeader->required[] = $property->field;
                }
                else{
                    if($property->key != "PRI"){
                        $property->object_types .= "|null";
                    }
                }

                if(str_contains($property->object_types,"null")){
                    $property->default_value = "null";
                }
                else if(str_contains($property->object_types,"string")){
                    $property->default_value = "''";
                }
                else{
                    $property->default_value = 0;
                }
            }

            if(count($tableDataHeader->dataKeys) > 0){
                $tableDataHeader->dataKeysString = implode(",",$tableDataHeader->dataKeys);
                $tableDataHeader->dataKeysString = "'".$tableDataHeader->dataKeysString."'";
                $tableDataHeader->dataKeysString = str_replace(",","','",$tableDataHeader->dataKeysString);
            }
            
            if(count($tableDataHeader->dataKeysPrimary) > 0){
                $tableDataHeader->dataKeysPrimaryString = implode(",",$tableDataHeader->dataKeysPrimary);
                $tableDataHeader->dataKeysPrimaryString = "'".$tableDataHeader->dataKeysPrimaryString."'";
                $tableDataHeader->dataKeysPrimaryString = str_replace(",","','",$tableDataHeader->dataKeysPrimaryString);   
            }
            
            if(count($tableDataHeader->dataKeysAutoInc) > 0){
                $tableDataHeader->dataKeysAutoIncString = implode(",",$tableDataHeader->dataKeysAutoInc);
                $tableDataHeader->dataKeysAutoIncString = "'".$tableDataHeader->dataKeysAutoIncString."'";
                $tableDataHeader->dataKeysAutoIncString = str_replace(",","','",$tableDataHeader->dataKeysAutoIncString);   
            }
            
            if(count($tableDataHeader->dataKeysUnique) > 0){
                $tableDataHeader->dataKeysUniqueString = implode(",",$tableDataHeader->dataKeysUnique);
                $tableDataHeader->dataKeysUniqueString = "'".$tableDataHeader->dataKeysUniqueString."'";
                $tableDataHeader->dataKeysUniqueString = str_replace(",","','",$tableDataHeader->dataKeysUniqueString);   
            }
            
            if(count($tableDataHeader->required) > 0){
                $tableDataHeader->requiredString = implode(",",$tableDataHeader->required);
                $tableDataHeader->requiredString = "'".$tableDataHeader->requiredString."'";
                $tableDataHeader->requiredString = str_replace(",","','",$tableDataHeader->requiredString);   
            }
            
            if(count($tableDataHeader->data_properties) > 0){
                $tableDataHeader->data_propertiesString = implode(",",$tableDataHeader->data_properties);
                $tableDataHeader->data_propertiesString = "'".$tableDataHeader->data_propertiesString."'";
                $tableDataHeader->data_propertiesString = str_replace(",","','",$tableDataHeader->data_propertiesString);   
            }
            
            if(count($tableDataHeader->data_property_types) > 0){
                $tableDataHeader->data_property_typesString = json_encode($tableDataHeader->data_property_types);
                $tableDataHeader->data_property_typesString = str_replace(["{","}"],"",$tableDataHeader->data_property_typesString);
                $tableDataHeader->data_property_typesString = str_replace(":","=>",$tableDataHeader->data_property_typesString);
            }
            
        }
        return $tables;
    }

    /** @return TableDataHeader[] */
    private static function initiateAndRetrieveTableNames(): array{
        $sql = "SHOW TABLES";
        $statement = self::$connection->prepare($sql);
        $statement->execute();
        $tables = $statement->fetchAll(mode:PDO::FETCH_COLUMN);
        $tablesData = [];
        foreach ($tables as $table_name){
            $table = new TableDataHeader();
            $table->table_name = $table_name;
            $tablesData[] = $table;
        }
        return $tablesData;
    }

    private static function retrieveTableProperties(TableDataHeader $dataHeader): TableDataHeader{
        $sql = "DESCRIBE $dataHeader->table_name";
        $statement = self::$connection->prepare($sql);
        $statement->execute();
        $properties = $statement->fetchAll();
        $dataHeader->properties = [];
        foreach ($properties as $property){
            $propertyData = new TableDataProperty();
            $propertyData->field = $property["Field"] ?? "";
            $propertyData->type = $property["Type"] ?? "";
            $propertyData->null = $property["Null"] ?? "";
            $propertyData->key = $property["Key"] ?? "";
            $propertyData->default = $property["Default"] ?? "";
            $propertyData->extra = $property["Extra"] ?? "";
            $dataHeader->properties[] = $propertyData;
        }
        return $dataHeader;
    }
}