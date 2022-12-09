<?php

namespace Ailabph\AilabCore;

use PDO;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class GeneratorClassPhp
{
    private static \PDO $connection;

    public static function run(){
        $tables = GeneratorExtractTablesData::getTablesInfo();
        $loader = new FilesystemLoader(__DIR__."/../tpl");
        $twig = new Environment($loader);

        foreach ($tables as $table){
            $classFile = $twig->render("TableClass.twig",["table"=>$table]);
            $classFile = "<?php".PHP_EOL.$classFile;
            self::writeClass($classFile,$table->table_name);

            $classListFile = $twig->render("TableClassList.twig",["table"=>$table]);
            $classListFile = "<?php".PHP_EOL.$classListFile;
            self::writeClass($classListFile,$table->table_name."List");
        }
    }

    public static function writeClass(string $classFile, string $table_name){
        try{
            $file_location = Config::getBaseDirectory() . "/App/DBClassGenerator/DB/".$table_name.".php";
            $fh = fopen($file_location,"w");
            fwrite($fh,$classFile);
            if(!file_exists( $file_location)){
                Assert::throw("class file not found");
            }
            return fclose($fh);
        }catch(\Exception $e){
            Assert::throw($e->getMessage());
        }
    }
}