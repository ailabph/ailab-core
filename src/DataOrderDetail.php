<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataOrderDetail
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $order_detail = Tools::appClassExist("order_detail");
        $order_detailX = Tools::appClassExist("order_detailX");
        self::$initiated = true;
    }

    public static function get(int|string|DB\order_detail $order_detail, bool $baseOnly = false): DB\order_detail|DB\order_detailX{
        self::init();
        return DataGeneric::get(
            base_class: "order_detail",
            extended_class: "order_detailX",
            dataObj: $order_detail,
            priKey: "id",
            uniKey: "",
            baseOnly:$baseOnly
        );
    }
}