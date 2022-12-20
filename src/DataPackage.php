<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;

class DataPackage
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $package_header = Tools::appClassExist("package_header");
        $package_headerX = Tools::appClassExist("package_headerX");
        self::$initiated = true;
    }

    public static function get(DB\package_header|string|int $package, bool $baseOnly = false): DB\package_headerX|DB\package_header
    {
        self::init();
        return DataGeneric::get(
            base_class: "package_header",
            extended_class: "package_headerX",
            dataObj: $package,
            priKey: "id",
            uniKey:"package_tag",
            baseOnly:$baseOnly
        );
    }
}