<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataProducts
{
    public static function get(DB\products|string|int $products): DB\productsX
    {
        if(!class_exists(DB\productsX::class)){
            Assert::throw("must implement productsX");
        }
        $to_return = new DB\productsX();
        $get_method = "";

        if($products instanceof DB\productsX){
            $get_method = ", via passed productsX";
            $to_return = $products;
        }
        else if($products instanceof DB\package_header){
            $get_method = ", via conversion to productsX id:$products->id";
            $to_return = new DB\productsX(["id"=>$products->id]);
        }

        if($to_return->isNew() && is_numeric($products)){
            $get_method = ", via product id:$products";
            $to_return = new DB\productsX(["id"=>$products]);
        }

        if($to_return->isNew() && is_string($products)){
            $get_method = ", via product_tag:$products";
            $to_return = new DB\productsX(["product_tag"=>$products]);
        }

        if($to_return->isNew()){
            Assert::throw("unable to retrieve product".$get_method);
        }

        return $to_return;
    }
}