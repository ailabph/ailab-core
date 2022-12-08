<?php

namespace Ailabph\AilabCore;
use Exception;
use PDO;
use PDOStatement;

class Connection
{
    static protected ?PDO $primary_connection = null;

    static protected ?PDO $secondary_connection = null;

    public static function reset()
    {
        global $conn;
        $conn = null;
        self::$primary_connection = null;
        self::$secondary_connection = null;
    }

    protected static function syncConnections():void{
        global $conn;

        if($conn instanceof PDO && is_null(self::$primary_connection)){
            self::$primary_connection = $conn;
        }

        if(self::$primary_connection instanceof PDO && !($conn instanceof PDO)){
            $conn = self::$primary_connection;
        }
    }

    protected static function init():void{
        self::syncConnections();
        global $conn;
        if(is_null(self::$primary_connection)){
            try{
                self::$primary_connection = new PDO(
                    "mysql:host=".Config::getConfig()->db_host.";dbname=".Config::getConfig()->db_name,
                    Config::getConfig()->db_user,
                    Config::getConfig()->db_pass,
                    [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);

                self::$secondary_connection = new PDO(
                    "mysql:host=".Config::getConfig()->db_host.";dbname=".Config::getConfig()->db_name,
                    Config::getConfig()->db_user,
                    Config::getConfig()->db_pass,
                    [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);
                $conn = self::$primary_connection;
            }catch (Exception $e){
                Tools::log($e->getMessage(),"connection",true);
                die("unable to connect to database");
            }
        }
    }

    public static function getPrimaryConnection(): PDO{
        self::init();
        return self::$primary_connection;
    }

    public static function getSecondaryConnection(): PDO{
        self::init();
        return self::$secondary_connection;
    }

    public static function startTransaction(): void{
        self::init();
        if(self::$primary_connection->inTransaction()) Assert::throw("already in transaction");
        global $lock;
        $lock = true;
        self::$primary_connection->beginTransaction();
    }

    /**
     * @throws Exception
     */
    public static function commit():void{
        self::init();
        if(!self::$primary_connection->inTransaction()) Assert::throw("unable to commit, not in transaction");
        self::$primary_connection->commit();
    }

    /**
     * @throws Exception
     */
    public static function rollback():void{
        self::init();
        if(!self::$primary_connection->inTransaction()) Assert::throw("unable to rollback, not in transaction");
        self::$primary_connection->rollBack();
    }

    /**
     * @throws Exception
     */
    public static function executeQuery(string $query, array $param = [], bool $use_secondary = false): bool|PDOStatement
    {
        if($use_secondary){
            $statement = self::getSecondaryConnection()->prepare($query);
        }
        else{
            $statement = self::getPrimaryConnection()->prepare($query);
        }
        try{
            $statement->execute($param);;
        }catch (Exception $e){
            Tools::log($e->getMessage(),"query",true);
            Tools::log($statement->queryString,"query",true);
            Tools::log(print_r($param,true),"query",true);
            Tools::log(message:"stack trace",category:"query",force_write:true,print_trace:true);
            Assert::throw("Something went wrong, please check with the Administrator");
        }
        return $statement;
    }


}