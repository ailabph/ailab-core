<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$statement = AilabCore\Connection::getConnection()
    ->prepare("ALTER TABLE `points_log` ADD `snapshot_account` JSON NULL, ADD `snapshot_wallet` JSON NULL AFTER `snapshot_account`;");
$statement->execute();