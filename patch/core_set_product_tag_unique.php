<?php
// ALTER TABLE `products` ADD UNIQUE(`product_tag`);
use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$st = AilabCore\Connection::getConnection()->prepare(" SHOW INDEX FROM products WHERE key_name = 'product_tag' ");
$st->execute();

if($st->rowCount() == 0){
    $statement = AilabCore\Connection::getConnection()->prepare("ALTER TABLE `products` ADD UNIQUE(`product_tag`);");
    $statement->execute();
}

