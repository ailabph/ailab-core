<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$statement = AilabCore\Connection::getConnection()
    ->prepare("ALTER TABLE `points_log` ADD `variant_id` INT NULL;");
$statement->execute();