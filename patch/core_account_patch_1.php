<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$res = AilabCore\Connection::executeQuery("SHOW COLUMNS FROM account WHERE Field = 'sponsor_id'",[]);
if($res->rowCount() == 0){
    AilabCore\Connection::executeQuery(" ALTER TABLE `account` ADD `sponsor_id` INT NOT NULL DEFAULT '-987654321'; ");
}

$res = AilabCore\Connection::executeQuery("SHOW COLUMNS FROM account WHERE Field = 'placement_id'",[]);
if($res->rowCount() == 0){
    AilabCore\Connection::executeQuery(" ALTER TABLE `account` ADD `placement_id` INT NOT NULL DEFAULT '-987654321'; ");
}
