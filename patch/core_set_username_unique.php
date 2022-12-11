<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$st = AilabCore\Connection::getConnection()->prepare("SHOW INDEX FROM user WHERE key_name = 'username' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `user` ADD UNIQUE(`username`);");
    $statement->execute();
}

