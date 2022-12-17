<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataInventoryOrder
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $inventory_order = Tools::appClassExist("inventory_order");
        $inventory_orderX = Tools::appClassExist("inventory_orderX");
        self::$initiated = true;
    }

    static public function get(int|string|DB\inventory_order $inventory_order, bool $baseOnly = false): DB\inventory_orderX|DB\inventory_order{
        self::init();
        return DataGeneric::get(
            base_class: "inventory_order",
            extended_class: "inventory_orderX",
            dataObj: $inventory_order,
            priKey: "id",
            uniKey:"hash",
            baseOnly:$baseOnly
        );
    }
}