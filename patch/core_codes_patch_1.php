<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

try{
    $st = AilabCore\Connection::getConnection()->prepare(" SHOW COLUMNS FROM codes LIKE 'variant_tag'; ");
    $st->execute();

    if($st->rowCount() == 0){
        $statement = AilabCore\Connection::getConnection()->prepare(" ALTER TABLE `codes` ADD `variant_tag` VARCHAR(255) NULL AFTER `variant_id`;  ");
        $statement->execute();
    }
}catch (Exception $e){}

try{
    $st = AilabCore\Connection::getConnection()->prepare(" SHOW COLUMNS FROM codes LIKE 'product_tag'; ");
    $st->execute();

    if($st->rowCount() == 0){
        $statement = AilabCore\Connection::getConnection()->prepare(" ALTER TABLE `codes` ADD `product_tag` VARCHAR(255) NULL AFTER `product_id`;  ");
        $statement->execute();
    }
}catch (Exception $e){}
