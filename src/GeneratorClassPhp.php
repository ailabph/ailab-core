<?php

namespace Ailabph\AilabCore;

use PDO;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class GeneratorClassPhp
{
    private static \PDO $connection;

    public static function run(bool $restrict_to_local = true)
    {
        if($restrict_to_local && Config::getEnv() != Config::ENV["local"]) {
            Logger::add("skip generate php db class, env not local","patch",__LINE__,true);
            return;
        }
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

    private static function writeClass(string $classFile, string $table_name): void
    {
        try{
            $file_location = Config::getBaseDirectory() . "/App/DBClassGenerator/DB/".$table_name.".php";
            $fh = fopen($file_location,"w");
            fwrite($fh,$classFile);
            if(!file_exists( $file_location)){
                Assert::throw("class file not found");
            }
            fclose($fh);
            return;
        }catch(\Exception $e){
            Assert::throw($e->getMessage());
        }
    }
}