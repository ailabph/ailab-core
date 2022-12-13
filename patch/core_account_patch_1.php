<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$statement = AilabCore\Connection::getConnection()
    ->prepare("ALTER TABLE `account` ADD `sponsor_id` INT NOT NULL DEFAULT '-987654321' AFTER `placement_account_id`, ADD `placement_id` INT NOT NULL DEFAULT '-987654321' AFTER `sponsor_id`; ");
$statement->execute();
