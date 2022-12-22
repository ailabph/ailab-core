<?php

namespace Ailabph\AilabCore;

use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use PDO;
use ReflectionProperty;

abstract class TableClass implements TableClassI, Loggable
{
    const UNDEFINED_STRING = "ailab_core_undefined";
    const UNDEFINED_NUMBER = -987654321;
    const BASE_NAME_SPACE = "App\DBClassGenerator\DB";

    private bool $isNew = true;
    protected string $table_name = "";
    protected array $dataKeys = [];
    protected array $dataKeysPrimary = [];
    protected array $dataKeysAutoInc = [];
    protected array $dataKeysUnique = [];
    protected array $data_properties_index = [];
    protected array $required = [];
    protected array $data_properties = [];
    protected array $data_property_types = [];
    protected bool $use_secondary_connection = false;
    protected PDO $pdoConnection;

    public function __construct(array $param, bool $secondary_connection = false){
        if(empty($this->table_name)) Assert::throw("table name required");
        if(empty($this->data_properties)) Assert::throw("data properties required");
        $this->use_secondary_connection = $secondary_connection;
        $this->pdoConnection = Connection::getConnection($this->use_secondary_connection);
        $this->loadValues($param,true);
        if(count($param) > 0) $this->getRecordAndLoadValues();
    }

    #region GETTERS

    public function getValue(string $property){
        $this->propertyExists($property);
        return $this->{$property};
    }

    public function getOrig(string $property){
        $this->propertyExists($property);
        $property_orig = $property . "_orig";
        if(!property_exists($this,$property_orig)) Assert::throw("property:$property_orig does not exist");
        return $this->{$property_orig};
    }

    public function getDefault(string $property){
        $this->propertyExists($property);
        $property_default = $property . "_default";
        if(!property_exists($this,$property_default)) Assert::throw("property:$property_default does not exist");
        return $this->{$property_default};
    }

    public function getTableName(bool $forQuery = false): string{
        return $forQuery ? "`$this->table_name`" : $this->table_name;
    }

    public function getType(string $property): string{
        self::propertyExists($property);
        if(!isset($this->data_property_types[$property]))
            Assert::throw("property:$property does not have a type in ".self::getTableName());
        return strtolower($this->data_property_types[$property]);
    }

    public function getPrimaryKey():string|false{
        return count($this->dataKeysPrimary) > 0 ? $this->dataKeysPrimary[0] : false;
    }

    #endregion


    #region CHECKERS

    public function isNew(): bool{
        return $this->isNew;
    }

    #[Pure] public function recordExists(): bool{
        return !$this->isNew();
    }

    public function propertyExists(string $property): bool{
        if(!in_array($property,$this->data_properties)){
            Assert::throw("property:$property does not exist in ".$this->getTableName());
        }
        return true;
    }

    public function hasChange(string $property): bool{
        $default_value = $this->getDefault($property);
        $original_value = $this->getOrig($property);
        $current_value = $this->getValue($property);

        if($this->isNew()){
            return $default_value !== $current_value;
        }
        else{
            return $original_value !== $current_value;
        }
    }

    public function hasAnyChanges():bool{
        $hasChanges = false;
        foreach($this->data_properties as $property){
            if($this->hasChange($property)){
                $hasChanges = true;
                break;
            }
        }
        return $hasChanges;
    }

    public function hasValue(string $property): bool{
        self::propertyExists($property);
        if($this->hasPlaceholderValue($property)){
            return false;
        }
        if($this->isNew()){
            $default_value = $this->getDefault($property);
            $current_value = $this->getValue($property);
            return $default_value != $current_value;
        }
        else{
            return $this->hasChange($property);
        }
    }

    public function hasAnyValue():bool{
        $hasValue = false;
        foreach ($this->data_properties as $property) {
            if($this->hasValue($property)){
                $hasValue = true;
                break;
            }
        }
        return $hasValue;
    }

    public function hasPlaceholderValue(string $property):bool{
        $value = $this->getValue($property);
        return $value == self::UNDEFINED_STRING || $value == self::UNDEFINED_NUMBER;
    }

    public function hasAutoIncPrimaryKey(): bool
    {
        $primaryKey = $this->getPrimaryKey();
        if(!$primaryKey) return false;
        return in_array($primaryKey,$this->dataKeysAutoInc);
    }

    public function hasPrimaryKey(): bool{
        return !empty($this->getPrimaryKey());
    }

    public function isIntegerPrimaryKey(string $property): bool{
        $this->propertyExists($property);
        if(!in_array($property,$this->dataKeysPrimary)) return false;
        $type = Tools::getPhpTypeFromSqlType($this->getType($property));
        return $type == Tools::INT;
    }

    public function hasIntegerPrimaryKey(): bool{
        if(!$this->hasPrimaryKey()) return false;
        $type = Tools::getPhpTypeFromSqlType($this->getType($this->getPrimaryKey()));
        return $type == Tools::INT;
    }

    public function hasNonAutoIncIntPrimaryKey(): bool{
        return
            $this->hasIntegerPrimaryKey()
            && !$this->hasAutoIncPrimaryKey();
    }

    // EXPERIMENTAL METHOD TO CHECK IF VALUE CAN BE SAFELY ASSIGNED TO PROPERTY
    public function checkValueType(string $property, $value): bool {
        // Reflect on the property
        $reflection = new ReflectionProperty($this, $property);
        $type = $reflection->getType();

        // Check if the property has a declared type
        if ($type === null) {
            // If the property has no declared type, any value can be assigned to it
            return true;
        }

        // Get the name of the declared type
        $typeName = $type->getName();

        // Check if the value is of the correct type
        $is_numeric = ["int","float"];
        $boolean_value = [1,0,true,false];
        $typeName = explode("|",$typeName);

        if($is_numeric($value)){
            return array_intersect($is_numeric,$typeName) > 0;
        }

        if(is_null($value)){
            return in_array("null",$typeName);
        }

        if(is_string($value)){
            return in_array("string",$typeName);
        }

        if(in_array("bool",$typeName)){
            return in_array($value,$boolean_value);
        }

        return $value instanceof $typeName;
    }

    #endregion END CHECKERS


    #region QUERY ACTIONS

    #[ArrayShape(["where" => "string", "param" => "array"])]
    private function buildWhereParamForQuery(bool $throwIfNoKeys = true, string $property_divider = "\n\t "):array{
        $where = "";
        $param = [];
        foreach ($this->dataKeysPrimary as $key){
            if(!empty($this->{$key}) && ($this->{$key} != self::UNDEFINED_NUMBER && $this->{$key} != self::UNDEFINED_STRING)){
                $where .= " WHERE \n\t ".$this->wrapPropertyForQuery($key)." = :$key ";
                $param[":$key"] = $this->{$key};
                break;
            }
        }

        if(empty($where)){
            foreach ($this->dataKeysUnique as $key){
                if(!empty($this->{$key}) && ($this->{$key} != self::UNDEFINED_NUMBER && $this->{$key} != self::UNDEFINED_STRING)){
                    $where .= " WHERE $property_divider".$this->wrapPropertyForQuery($key)."=:$key ";
                    $param[":$key"] = $this->{$key};
                    break;
                }
            }
        }

        if(empty($where) && $throwIfNoKeys){
            Assert::throw("No primary or unique keys to build query");
        }

        if(empty($where)){
            foreach ($this->data_properties as $property){
                if(!empty($this->{$property})){
                    if($this->{$property} == self::UNDEFINED_NUMBER || $this->{$property} == self::UNDEFINED_STRING){
                        continue;
                    }
                    if($this->{$property} == $this->getDefault($property)){
                        continue;
                    }
                    if(empty($where)){
                        $where .= " WHERE ";
                    }
                    else{
                        $where .= " AND ";
                    }
                    $where .="$property_divider". $this->wrapPropertyForQuery($property)." = :$property ";
                    $param[":$property"] = $this->{$property};
                }
            }
        }

        return ["where"=>$where,"param"=>$param];
    }

    public function get(string $where = "", array $param = [], string $order = "", string $select = " * ", string $join = ""): object|bool|array
    {
        return $this->getRecord(where:$where,param: $param,getAll: true,order: $order,select: $select,join: $join);
    }

    private function getRecord(string $where = "", array $param = [], bool $getAll = false, string $order = "", string $select = " * ", string $join = ""): array|object|false{
        if(empty($where)){
            $whereParam = $this->buildWhereParamForQuery(throwIfNoKeys: false,property_divider: "");
            $where = $whereParam["where"];
            $param = $whereParam["param"];
        }

        if(empty($where)) return false;

        $sql =  "SELECT ".$select." FROM ".$this->getTableName(true)." ";
        $sql .= $join." ".$where." ".$order;

        self::addLog($this->formatQueryString($sql,$param),__LINE__);
        $statement = $this->pdoConnection->prepare($sql);

        try{
            $statement->execute($param);
        }catch (Exception $e){
            Tools::logIncident(message:$e->getMessage(), category: "query",printLoggedDevice: true, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql, category: "query",printLoggedDevice: false, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($param,true), category: "query",printLoggedDevice: false, printStackTrace: true);
            Assert::throw("Something went wrong when retrieving the list of data");
        }


        if($getAll){
            $items = [];
            while($record = $statement->fetchObject()){
                array_push($items,$record);
            }
        }
        else{
            $items = $statement->rowCount() > 0 ? $statement->fetchObject() : false;
        }

        return $items;
    }

    private function getRecordAndLoadValues(){
        if($item = $this->getRecord()){
            $this->loadValues(data:$item,isNew: false);
        }
    }

    public function save(string $where = "", array $param = []){
        self::addLog("saving ".$this->getTableName(),__LINE__);
        if(!$this->hasAnyChanges()) return;
        self::checkRequiredValues();
        if($this->isNew()){
            self::addLog("new record, inserting...",__LINE__);
            $this->insert();
        }
        else{
            self::addLog("existing record, updating...",__LINE__);
            $this->update($where,$param);
        }
    }

    private function update(string $where = "", array $param = []){
        self::addLog("updating ".$this->getTableName(),__LINE__);
        if(!$this->hasAnyChanges()) {
            $error_message = "nothing to update, property has no changes";
            Tools::logIncident(message:$error_message, category: "query",printLoggedDevice: true, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($this,true), category: "query",printLoggedDevice: false, printStackTrace: true);
            Assert::throw($error_message);
        }
        if(empty($where)){
            $whereParam = $this->buildWhereParamForQuery(throwIfNoKeys: true);
            $where = $whereParam["where"];
            $param = $whereParam["param"];
        }

        if(empty($where)) Assert::throw("Unable to update, where is empty");

        $insertSection = "";
        foreach($this->data_properties as $property){
            if(in_array($property,$this->dataKeysPrimary)) continue;
            if($this->hasChange($property)){
                if(!empty($insertSection)) $insertSection .= ", \n\t ";
                $insertSection .= $this->wrapPropertyForQuery($property) . " = :$property ";
                $param[":$property"] = $this->{$property};
            }
        }

        if(empty($insertSection)){
            $error_message = "section in sql for update is empty";
            Tools::logIncident(message:$error_message, category: "query",printLoggedDevice: true, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($this,true), category: "query",printLoggedDevice: false, printStackTrace: true);
            Assert::throw($error_message);
        }

        $sql =  "UPDATE ";
        $sql .= "\n\t ".$this->getTableName(forQuery: true)." ";
        $sql .= "\nSET";
        $sql .= "\n\t ".$insertSection." ";
        $sql .= "\n".$where;
        self::addLog(PHP_EOL.$this->formatQueryString($sql,$param),__LINE__);

        $statement = $this->pdoConnection->prepare($sql);
        try{
            $statement->execute($param);
        }catch (Exception $e){
            Tools::logIncident(message:$e->getMessage(),category:"query",printLoggedDevice: true, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql, category: "query",printLoggedDevice: false, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($param,true),category:"query",printLoggedDevice: false, printStackTrace: true);
            Assert::throw("Something went wrong when updated a record");
        }
//        if($statement->rowCount() == 0){
//            Tools::logIncident(message:"No rows affected during update query",category:"query",printLoggedDevice: true, printStackTrace: false);
//            Tools::logIncident(message:PHP_EOL.$sql, category: "query",printLoggedDevice: false, printStackTrace: false);
//            Tools::logIncident(message:PHP_EOL.print_r($param,true),category:"query",printLoggedDevice: false, printStackTrace: true);
//            Assert::throw("Something went wrong when updating a record");
//        }
        $this->importOriginalValuesFromCurrentValues();

    }

    private function insert(){
        self::addLog("inserting into ".$this->getTableName(),__LINE__);
        if(!$this->hasAnyValue()) Assert::throw("Nothing to insert");
        $insertProperties = "";
        $insertValues = "";
        $insertParam = [];

        if($this->hasNonAutoIncIntPrimaryKey()){
            $primaryKey = $this->getPrimaryKey();
            if(empty($primaryKey)) Assert::throw("expected to have primary key",__LINE__);
            self::addLog("non auto increment integer primary key detected, using hrtime for value in primary key:$primaryKey",__LINE__);
            $this->{$primaryKey} = hrtime(true);
            self::addLog("primary key value:".$this->{$primaryKey},__LINE__);
        }

        foreach ($this->data_properties as $property){
            if(in_array($property,$this->dataKeysAutoInc)) continue;
            if($this->hasValue($property)){
                if(!empty($insertProperties)){
                    $insertProperties .= ", ";
                    $insertValues .= ", ";
                }
                $insertProperties .= "\n\t ";
                $insertValues .= "\n\t ";

                $insertProperties .= $this->wrapPropertyForQuery($property);
                $insertValues .= ":$property";
                $insertParam[":$property"] = $this->{$property};
            }
        }

        $sql =  "INSERT INTO";
        $sql .= " ".$this->getTableName(forQuery: true)." \n ";
        $sql .= " ($insertProperties) \n ";
        $sql .= " VALUES \n ";
        $sql .= " ($insertValues)";

        if(empty($insertProperties)) {
            Tools::logIncident(message:"Unable to build an insert query string",category:"query",printLoggedDevice: true,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql,category:"query",printLoggedDevice: false,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($insertParam,true),category:"query",printLoggedDevice: false,printStackTrace: true);
            Assert::throw("Unable to build an insert query string");
        }

        self::addLog(PHP_EOL.$this->formatQueryString($sql,$insertParam),__LINE__);
        $statement = $this->pdoConnection->prepare($sql);
        try{
            $statement->execute($insertParam);
        }catch (Exception $e){
            Tools::logIncident(message:"Insert query failed",category: "query",printLoggedDevice: true,printStackTrace: false);
            Tools::logIncident(message:$e->getMessage(),category:"query",printLoggedDevice: false,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql,category: "query",printLoggedDevice: false,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($insertParam,true),category:"query",printLoggedDevice: false,printStackTrace: true);
            Assert::throw("Something went wrong with added new record");
        }

        if($statement->rowCount() == 0){
            Tools::logIncident(message:"No rows affected during insert query",category:"query",printLoggedDevice: true,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql,category:"query",printLoggedDevice: false,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($insertParam,true),category:"query",printLoggedDevice: false,printStackTrace: true);
            Assert::throw("Something went wrong when updating a record");
        }

        $insertId = $this->pdoConnection->lastInsertId();
        if($insertId > 0 && count($this->dataKeysPrimary) > 0){
            $this->{$this->dataKeysPrimary[0]} = $insertId;
        }
        $this->isNew = false;
        $this->importOriginalValuesFromCurrentValues();
    }

    public function delete(bool $softDelete = false){
        if($this->isNew()) Assert::throw("unable to delete a new record");
        $whereParam = $this->buildWhereParamForQuery(throwIfNoKeys: true);
        $sql =  "DELETE FROM";
        $sql .= " ".$this->getTableName(forQuery: true);
        $sql .= $whereParam["where"];

        self::addLog($sql,__LINE__);
        self::addLog(print_r($whereParam["param"],true),__LINE__);
        $statement = $this->pdoConnection->prepare($sql);
        try{
            $statement->execute($whereParam["param"]);
        }catch (Exception $e){
            Tools::logIncident(message:"delete failed",category:"query",printLoggedDevice: true,printStackTrace: false);
            Tools::logIncident(message:$e->getMessage(),category:"query",printLoggedDevice: true,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql,category:"query",printLoggedDevice: false,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($whereParam,true),category:"query",printLoggedDevice: false,printStackTrace: true);
            Assert::throw("Something went wrong with deleting the record");
        }

        if($statement->rowCount() == 0){
            Tools::logIncident(message:"No rows affected during delete query",category:"query",printLoggedDevice: true,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql,category:"query",printLoggedDevice: false,printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($whereParam,true),category:"query",printLoggedDevice: false,printStackTrace: true);
            Assert::throw("Something went wrong when deleting a record");
        }

        $this->isNew = true;
        $this->resetAllValues();
    }

    public function refresh(){
        self::addLog("refreshing data of ".$this->getTableName(),__LINE__);
        if($this->isNew()) return;
        $this->getRecordAndLoadValues();
    }

    #endregion END OF QUERY ACTIONS


    #region UTILITIES

    public function loadValues(array|object $data, bool $isNew = false, array $exclude = [], bool $manualLoad = false, bool $strict = false){
        $this->isNew = $isNew;
        foreach ($data as $property => $value){
            if(!in_array($property,$this->data_properties) && $strict) continue;
            if(!property_exists($this,$property)) continue;
            if(in_array($property, $exclude)) continue;
            // suppress errors of incompatible types
            try{ $this->{$property} = $value; }catch (\TypeError $e){}
            if(!$isNew && !$manualLoad){
                if(in_array($property,$this->data_properties)){
                    if(!property_exists($this,$property."_orig")){
                        Assert::throw("property:$property has no _orig property");
                    }
                    // suppress errors of incompatible types
                    try{ $this->{$property."_orig"} = $value; }catch (\TypeError $e){}
                }
            }
        }
        if(method_exists($this,"loadFunc")){
            $this->loadFunc();
        }
    }

    #[Pure] private function wrapPropertyForQuery(string $prop): string{
        return $this->getTableName(true).".`$prop`";
    }

    private function importOriginalValuesFromCurrentValues(){
        foreach ($this->data_properties as $property){
            $this->{$property."_orig"} = $this->getValue($property);
        }
    }

    protected function resetAllValues(){
        foreach ($this->data_properties as $property){
            $this->{$property."_default"} = $this->getDefault($property);
        }
    }

    protected function checkRequiredValues(){
        foreach ($this->required as $property) {
            if(in_array($property,$this->dataKeysAutoInc)) continue;
            if($this->isIntegerPrimaryKey($property)) continue;
            $property_type = Tools::getPhpTypeFromSqlType($this->getType($property));
            if($property_type == Tools::INT || $property_type == Tools::FLOAT){
                if($this->{$property} == self::UNDEFINED_NUMBER){
                    Assert::throw("Unable to save, property:$property is required");
                }
            }
            if($property_type == Tools::STRING){
                if($this->{$property} == self::UNDEFINED_STRING){
                    Assert::throw("Unable to save, property:$property is required");
                }
            }
        }
    }

    protected function formatQueryString(string $query, array $param):string{
        foreach ($param as $property => $value){
            $query = str_replace($property,"$property -> $value",$query);
        }
        return $query;
    }

    public static function addLog(string $log, int $line){
        Logger::add(msg:$log,category: "query",line:$line);
    }

    #endregion END OF UTILITIES
}