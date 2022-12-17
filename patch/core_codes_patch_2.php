<?php
use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$st = AilabCore\Connection::getConnection()->prepare(" SHOW COLUMNS FROM codes LIKE 'price_paid'; ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare(" ALTER TABLE `codes` ADD `price_paid` DECIMAL(16,2) NULL AFTER `pin`; ");
    $statement->execute();
}