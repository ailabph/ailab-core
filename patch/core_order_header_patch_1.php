<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$st = AilabCore\Connection::getConnection()->prepare(" SHOW INDEX FROM order_header WHERE key_name = 'hash' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `order_header` ADD UNIQUE(`hash`);");
    $statement->execute();
}