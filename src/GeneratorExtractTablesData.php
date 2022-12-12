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
            foreach ($tableDataHeader->properties as $property) {
                $tableDataHeader->data_properties[] = $property->field;
                $tableDataHeader->data_property_types[$property->field] = $property->type;

                self::parseAndCollectKeys($tableDataHeader, $property);
                self::parseAndCollectPrimaryKeys($tableDataHeader, $property);
                self::parseAndCollectUniqueKeys($tableDataHeader, $property);
                self::parseAndCollectAutoIncrement($tableDataHeader, $property);
                self::parseAndCollectRequiredProperties($tableDataHeader, $property);
                self::parseAndCollectPropertiesIndex($tableDataHeader, $property);
                self::setObjectType($property);
                self::setDefaultValues($property);
            }
            $tableDataHeader->data_propertiesString = Tools::convertArrayOfStringToString($tableDataHeader->data_properties,",","'");
            $tableDataHeader->dataKeysString = Tools::convertArrayOfStringToString($tableDataHeader->dataKeys,",","'");
            $tableDataHeader->dataKeysPrimaryString = Tools::convertArrayOfStringToString($tableDataHeader->dataKeysPrimary,",","'");
            $tableDataHeader->dataKeysUniqueString = Tools::convertArrayOfStringToString($tableDataHeader->dataKeysUnique,",","'");
            $tableDataHeader->dataKeysAutoIncString = Tools::convertArrayOfStringToString($tableDataHeader->dataKeysAutoInc,",","'");
            $tableDataHeader->requiredString = Tools::convertArrayOfStringToString($tableDataHeader->required,",","'");
            $tableDataHeader->data_properties_indexString = Tools::convertArrayOfStringToString($tableDataHeader->data_properties_index,",","'");
        }
        return $tables;
    }

    private static function parseAndCollectKeys(TableDataHeader $tableDataHeader, TableDataProperty $property){
        if($property->key == "PRI" || $property->key == "UNI"){
            $tableDataHeader->dataKeys[] = $property->field;
        }
    }
    private static function parseAndCollectPrimaryKeys(TableDataHeader $tableDataHeader, TableDataProperty $property){
        if($property->key == "PRI"){
            $tableDataHeader->dataKeysPrimary[] = $property->field;
        }
    }
    private static function parseAndCollectUniqueKeys(TableDataHeader $tableDataHeader, TableDataProperty $property){
        if($property->key == "UNI"){
            $tableDataHeader->dataKeysUnique[] = $property->field;
        }
    }
    private static function parseAndCollectAutoIncrement(TableDataHeader $tableDataHeader, TableDataProperty $property){
        if($property->extra == "auto_increment"){
            $tableDataHeader->dataKeysAutoInc[] = $property->field;
        }
    }
    private static function parseAndCollectRequiredProperties(TableDataHeader $tableDataHeader, TableDataProperty $property){
        if($property->null == "NO" && $property->extra != "auto_increment"){
            $tableDataHeader->required[] = $property->field;
        }
    }
    private static function parseAndCollectPropertiesIndex(TableDataHeader $tableDataHeader, TableDataProperty $property)
    {
        if ($property->key == "MUL") {
            $tableDataHeader->data_properties_index[] = $property->field;
        }
    }
    private static function setObjectType(TableDataProperty $property){
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
        if(empty($property->object_types)) Assert::throw("object type not detected");
        if($property->null == "YES" || $property->extra == "auto_increment"){
            $property->object_types .= "|null";
        }
    }
    private static function setDefaultValues(TableDataProperty $property){
        if(str_contains($property->object_types,"null")){
            $property->default_value = "null";
        }
        else{
            if($property->object_types == "int" || $property->object_types == "float"){
                $property->default_value = empty($property->default) ? 0 : $property->default;
            }
            else if($property->object_types == "string"){
                $property->default_value = empty($property->default) ? "''" : "'$property->default'";
            }
        }

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