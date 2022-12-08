<?php

namespace Ailabph\AilabCore;
use Exception;
use PDO;
use PDOStatement;

class Connection
{
    static protected ?PDO $connection = null;

    public static function reset()
    {
        global $conn;
        self::$connection = null;
        $conn = null;
    }

    protected static function syncConnections():void{
        global $conn;

        if($conn instanceof PDO && is_null(self::$connection)){
            self::$connection = $conn;
        }

        if(self::$connection instanceof PDO && !($conn instanceof PDO)){
            $conn = self::$connection;
        }
    }

    protected static function init():void{
        self::syncConnections();
        global $conn;
        if(is_null(self::$connection)){
            try{
                self::$connection = new PDO(
                    "mysql:host=".Config::getConfig()->db_host.";dbname=".Config::getConfig()->db_name,
                    Config::getConfig()->db_user,
                    Config::getConfig()->db_pass,
                    [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);
                $conn = self::$connection;
            }catch (Exception $e){
                Tools::log($e->getMessage(),"connection",true);
                die("unable to connect to database");
            }
        }
    }

    public static function getConnection(): PDO{
        self::init();
        return self::$connection;
    }

    public static function startTransaction(): void{
        self::init();
        if(self::$connection->inTransaction()) Assert::throw("already in transaction");
        global $lock;
        $lock = true;
        self::$connection->beginTransaction();
    }

    public static function commit():void{
        self::init();
        if(!self::$connection->inTransaction()) Assert::throw("unable to commit, not in transaction");
        self::$connection->commit();
    }

    public static function rollback():void{
        self::init();
        if(!self::$connection->inTransaction()) Assert::throw("unable to rollback, not in transaction");
        self::$connection->rollBack();
    }

    public static function executeQuery(string $query, array $param = []): bool|PDOStatement
    {
        $statement = self::getConnection()->prepare($query);
        try{
            $statement->execute($param);;
        }catch (Exception $e){
            Tools::log($e->getMessage(),"query",true);
            Assert::throw("Something went wrong, please check with the Administrator");
        }
        return $statement;
    }


}