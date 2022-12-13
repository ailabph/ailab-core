<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;

class DataPackage
{
    public static function get(DB\package_header|string|int $package): DB\package_headerX
    {
        if(!class_exists(DB\package_headerX::class)){
            Assert::throw("must implement package_headerX");
        }
        $to_return = new DB\package_headerX();
        $get_method = "";

        if($package instanceof DB\package_headerX){
            $get_method = ", via passed package_headerX";
            $to_return = $package;
        }
        else if($package instanceof DB\package_header){
            $get_method = ", via conversion to package_headerX id:$package->id";
            $to_return = new DB\package_headerX(["id"=>$package->id]);
        }

        if($to_return->isNew() && is_numeric($package)){
            $get_method = ", via package_id:$package";
            $to_return = new DB\package_headerX(["id"=>$package]);
        }

        if($to_return->isNew() && is_string($package)){
            $get_method = ", via package_tag:$package";
            $to_return = new DB\package_headerX(["package_tag"=>$package]);
        }

        if($to_return->isNew()){
            Assert::throw("unable to retrieve package".$get_method);
        }

        return $to_return;
    }
}