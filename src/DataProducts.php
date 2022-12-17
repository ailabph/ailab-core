<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataProducts
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $products = Tools::appClassExist("products");
        $productsX = Tools::appClassExist("productsX");
        self::$initiated = true;
    }

    public static function get(DB\products|string|int $products, bool $baseOnly = false): DB\productsX|DB\products
    {
        return DataGeneric::get(
            base_class: "products",
            extended_class: "productsX",
            dataObj: $products,
            priKey: "id",
            uniKey:"product_tag",
            baseOnly:$baseOnly
        );
    }
}