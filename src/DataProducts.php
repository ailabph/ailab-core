<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataProducts
{
    public static function get(DB\products|string|int $products, bool $pure = false): DB\productsX|DB\products
    {
        if(!class_exists(DB\productsX::class)){
            Assert::throw("must implement productsX");
        }
        $product_class_to_use = $pure ? DB\products::class : DB\productsX::class;
        $to_return = new $product_class_to_use();
        $get_method = "";

        if($products instanceof $product_class_to_use){
            $to_return = $products;
        }
        else if($products instanceof DB\products && !$pure){
            $get_method = ", via conversion to productsX id:$products->id";
            $to_return = new DB\productsX(["id"=>$products->id]);
        }

        if($to_return->isNew() && is_numeric($products)){
            $get_method = ", via product id:$products";
            $to_return = new $product_class_to_use(["id"=>$products]);
        }

        if($to_return->isNew() && is_string($products)){
            $get_method = ", via product_tag:$products";
            $to_return = new $product_class_to_use(["product_tag"=>$products]);
        }

        if($to_return->isNew()){
            Assert::throw("unable to retrieve product".$get_method);
        }

        return $to_return;
    }
}