<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$st = AilabCore\Connection::getConnection()->prepare(" SHOW INDEX FROM package_variant WHERE key_name = 'package_tag' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `package_variant` ADD UNIQUE(`package_tag`);");
    $statement->execute();
}