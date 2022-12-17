<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;

class DataPackageVariant
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $package_header = Tools::appClassExist("package_header");
        $package_variant = Tools::appClassExist("package_variant");
        $package_variantX = Tools::appClassExist("package_variantX");
        self::$initiated = true;
    }

    public static function get(DB\package_variant|string|int $variant, bool $baseOnly = false): DB\package_variantX|DB\package_variant
    {
        self::init();
        return DataGeneric::get(
            base_class: "package_variant",
            extended_class: "package_variantX",
            dataObj: $variant,
            priKey: "id",
            uniKey:"package_tag",
            baseOnly:$baseOnly
        );
    }

    public static function getDefault(int|string|DB\package_header $package_header): DB\package_variantX{
        $topVariant = new DB\package_variantList(
            " WHERE package_id=:package_id"
            ,[":package_id"=>$package_header->id]," ORDER BY id ASC LIMIT 1");
        if($topVariant->count() == 0) Assert::throw("No default package variant for package $package_header->package_tag");
        return $topVariant->fetch();
    }
}