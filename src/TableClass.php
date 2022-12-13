<?php

namespace Ailabph\AilabCore;

use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use PDO;

abstract class TableClass implements TableClassI
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

    public function getOrig(string $property){
        if(!in_array($property,$this->data_properties)) Assert::throw("property:$property does not exist");
        $property_orig = $property . "_orig";
        if(!property_exists($this,$property_orig)) Assert::throw("property:$property_orig does not exist");
        return $this->{$property_orig};
    }

    public function getDefault(string $property){
        if(!in_array($property,$this->data_properties)) Assert::throw("property:$property does not exist");
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
        if(!in_array($property,$this->data_properties)) Assert::throw("property:$property does not exist in this table");
        if(!property_exists($this,$property."_orig")) Assert::throw("original property:$property does not exist");
        return $this->{$property} != $this->{$property."_orig"};
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
        if(!in_array($property,$this->data_properties)) Assert::throw("property:$property does not exist");
        return !is_null($this->{$property});
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

    #endregion END CHECKERS



    #region QUERY ACTIONS

    #[ArrayShape(["where" => "string", "param" => "array"])]
    private function buildWhereParamForQuery(bool $throwIfNoKeys = true):array{
        $where = "";
        $param = [];
        foreach ($this->dataKeysPrimary as $key){
            if(!empty($this->{$key})){
                $where .= " WHERE \n\t ".$this->wrapPropertyForQuery($key)." = :$key ";
                $param[":$key"] = $this->{$key};
                break;
            }
        }

        if(empty($where)){
            foreach ($this->dataKeysUnique as $key){
                if(!empty($this->{$key})){
                    $where .= " WHERE \n\t ".$this->wrapPropertyForQuery($key)." = :$key ";
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
                    if(empty($where)){
                        $where .= " WHERE \n ";
                    }
                    else{
                        $where .= " AND \n ";
                    }
                    $where .="\t". $this->wrapPropertyForQuery($property)." = :$property ";
                    $param[":$property"] = $this->{$property};
                }
            }
        }

        if(empty($where)){
            Assert::throw("Unable to build a where for the query");
        }

        return ["where"=>$where,"param"=>$param];
    }

    public function get(string $where = "", array $param = [], string $order = "", string $select = " * ", string $join = ""): object|bool|array
    {
        return $this->getRecord(where:$where,param: $param,getAll: true,order: $order,select: $select,join: $join);
    }

    private function getRecord(string $where = "", array $param = [], bool $getAll = false, string $order = "", string $select = " * ", string $join = ""): array|object|false{
        if(empty($where)){
            $whereParam = $this->buildWhereParamForQuery(throwIfNoKeys: false);
            $where = $whereParam["where"];
            $param = $whereParam["param"];
        }

        $sql =
            "SELECT \n "
            ."\t ".$select."\n "
            ." FROM \n "
            ."\t ". $this->getTableName(true)." \n "
            ." ".$join." \n "
            ." ".$where." \n "
            ." ".$order;
        Tools::log($sql,"query");
        Tools::log(print_r($param,true),"query");
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
        if(!$this->hasAnyChanges()) return;
        self::checkRequiredValues();
        if($this->isNew()){
            $this->insert();
        }
        else{
            $this->update($where,$param);
        }
    }

    private function update(string $where = "", array $param = []){
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
                if(!empty($insertSection)) $insertSection .= ", \n\t\t ";
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

        $sql =  "UPDATE \n";
        $sql .= " \t ".$this->getTableName(forQuery: true)." \n ";
        $sql .= "SET \n ";
        $sql .= " \t\t ".$insertSection." \n ";
        $sql .= $where;
        Tools::log($sql,"query");
        Tools::log(print_r($param,true),"query");

        $statement = $this->pdoConnection->prepare($sql);
        try{
            $statement->execute($param);
        }catch (Exception $e){
            Tools::logIncident(message:$e->getMessage(),category:"query",printLoggedDevice: true, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql, category: "query",printLoggedDevice: false, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($param,true),category:"query",printLoggedDevice: false, printStackTrace: true);
            Assert::throw("Something went wrong when updated a record");
        }
        if($statement->rowCount() == 0){
            Tools::logIncident(message:"No rows affected during update query",category:"query",printLoggedDevice: true, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.$sql, category: "query",printLoggedDevice: false, printStackTrace: false);
            Tools::logIncident(message:PHP_EOL.print_r($param,true),category:"query",printLoggedDevice: false, printStackTrace: true);
            Assert::throw("Something went wrong when updating a record");
        }
        $this->resetOriginalValues();

    }

    private function insert(){
        if(!$this->hasAnyValue()) Assert::throw("Nothing to insert");
        $insertProperties = "";
        $insertValues = "";
        $insertParam = [];

        foreach ($this->data_properties as $property){
            if(in_array($property,$this->dataKeysAutoInc)) continue;
            if($this->hasValue($property)){
                if(!empty($insertProperties)){
                    $insertProperties .= ", ";
                    $insertValues .= ", ";
                }
                $insertProperties .= " \n\t\t ";
                $insertValues .= " \n\t\t ";

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
        $this->resetOriginalValues();
    }

    public function delete(bool $softDelete = false){
        if($this->isNew()) Assert::throw("unable to delete a new record");
        $whereParam = $this->buildWhereParamForQuery(throwIfNoKeys: true);
        $sql =  "DELETE FROM";
        $sql .= " ".$this->getTableName(forQuery: true);
        $sql .= $whereParam["where"];

        Tools::log($sql,"query");
        Tools::log(print_r($whereParam["param"],true),"query");
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
        $this->resetOriginalValues();
    }

    public function refresh(){
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
            $this->{$property} = $value;
            if(!$isNew && !$manualLoad){
                if(in_array($property,$this->data_properties)){
                    if(!property_exists($this,$property."_orig")){
                        Assert::throw("property:$property has no _orig property");
                    }
                    $this->{$property."_orig"} = $value;
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

    private function resetOriginalValues(){
        foreach ($this->data_properties as $property){
            $this->{$property."_orig"} = $this->getOrig($property);
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
            $property_type = Tools::getPhpTypeFromSqlType($this->getType($property));
            if($property_type == Tools::INT || $property_type == Tools::FLOAT){
                if($this->{$property} == -999){
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

    #endregion END OF UTILITIES
}