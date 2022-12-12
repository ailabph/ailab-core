<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$st = AilabCore\Connection::getConnection()->prepare(" SHOW INDEX FROM account WHERE key_name = 'account_code' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `account` ADD UNIQUE(`account_code`);");
    $statement->execute();
}

