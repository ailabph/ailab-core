<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$st = AilabCore\Connection::getConnection()->prepare(" SHOW INDEX FROM points_log WHERE key_name = 'user_id' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `points_log` ADD INDEX(`user_id`);");
    $statement->execute();
}


$st = AilabCore\Connection::getConnection()->prepare(" SHOW INDEX FROM points_log WHERE key_name = 'account_code' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `points_log` ADD INDEX(`account_code`);");
    $statement->execute();
}


$st = AilabCore\Connection::getConnection()->prepare(" SHOW INDEX FROM points_log WHERE key_name = 'account_id' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `points_log` ADD INDEX(`account_id`);");
    $statement->execute();
}


$st = AilabCore\Connection::getConnection()->prepare(" SHOW INDEX FROM points_log WHERE key_name = 'action' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `points_log` ADD INDEX(`action`);");
    $statement->execute();
}





