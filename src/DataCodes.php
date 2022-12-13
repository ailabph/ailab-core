<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;
class DataCodes
{
    public static function getRecord(DB\codes|string|int $codes): DB\codesX{
        $to_return = new DB\codesX();
        $get_method = "";

        if($codes instanceof DB\codesX){
            $get_method = ", via passed codesX object";
            $to_return = $codes;
        }
        else if($codes instanceof DB\codes){
            $get_method = ", via conversion to codesX object, id:$codes->id";
            $to_return = self::getRecord($codes->id);
        }

        if($to_return->isNew() && is_numeric($codes)){
            $get_method = ", via codes id:$codes";
            $to_return = new DB\codesX(["id"=>$codes]);
        }

        if($to_return->isNew() && is_string($codes)){
            $get_method = ", via code:$codes";
            $to_return = new DB\codesX(["code"=>$codes]);
        }

        if($to_return->isNew()){
            Assert::throw("codes record not retrieved from database".$get_method);
        }
        return $to_return;
    }
}