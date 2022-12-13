<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$statement = AilabCore\Connection::getConnection()
    ->prepare("ALTER TABLE `points_log` ADD `bonus_percentage` DECIMAL(16,2) NULL;");
$statement->execute();