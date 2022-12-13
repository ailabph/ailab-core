<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;

class DataPackageVariant
{
    public static function get(DB\package_variant|string|int $variant): DB\package_variantX
    {
        if(!class_exists(DB\package_variantX::class)){
            Assert::throw("must implement package_variantX");
        }
        $to_return = new DB\package_variantX();
        $get_method = "";

        if($variant instanceof DB\package_variantX){
            $get_method = ", via passed package_variantX";
            $to_return = $variant;
        }
        else if($variant instanceof DB\package_variant){
            $get_method = ", via conversion to package_variantX id:$variant->id";
            $to_return = new DB\package_variantX(["id"=>$variant->id]);
        }

        if($to_return->isNew() && is_numeric($variant)){
            $get_method = ", via variant id:$variant";
            $to_return = new DB\package_variantX(["id"=>$variant]);
        }

        if($to_return->isNew() && is_string($variant)){
            $get_method = ", via variant tag:$variant";
            $to_return = new DB\package_variantX(["package_tag"=>$variant]);
        }

        if($to_return->isNew()){
            Assert::throw("unable to retrieve variant".$get_method);
        }

        return $to_return;
    }
}