<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

try{
    $statement = AilabCore\Connection::getConnection()
        ->prepare("ALTER TABLE `points_log` ADD `sponsor_level` INT NULL;");
    $statement->execute();
}catch (Exception $e){}
