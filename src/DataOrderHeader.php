<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataOrderHeader
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $order_header = Tools::appClassExist("order_header");
        $order_headerX = Tools::appClassExist("order_headerX");
        self::$initiated = true;
    }

    public static function get(int|string|DB\order_header $order_header, bool $baseOnly = false): DB\order_headerX|DB\order_header{
        self::init();
        return DataGeneric::get(
            base_class: "order_header",
            extended_class: "order_headerX",
            dataObj: $order_header,
            priKey: "id",
            uniKey:"hash",
            baseOnly:$baseOnly
        );
    }
}