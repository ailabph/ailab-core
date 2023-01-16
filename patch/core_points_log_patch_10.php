<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

try{
    $statement = AilabCore\Connection::getConnection()
        ->prepare("ALTER TABLE `points_log` ADD `product_srp` DECIMAL(16,2) NULL;");
    $statement->execute();
}catch (Exception $e){}
