<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

try{
    $statement = AilabCore\Connection::getConnection()
        ->prepare("ALTER TABLE `points_log` ADD `variant_tag` VARCHAR(255) NULL;");
    $statement->execute();
}catch (Exception $e){}
