<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataCodeOwnershipLog
{
    public static bool $initiated = false;
    public static function init(){
        if(self::$initiated) return;
        $codes = Tools::appClassExist("codes");
        $user = Tools::appClassExist("user");
        $package_variant = Tools::appClassExist("package_variant");
        $code_ownership_log = Tools::appClassExist("code_ownership_log");
        $code_ownership_logX = Tools::appClassExist("code_ownership_logX");
        Tools::checkPropertiesExistInClass($code_ownership_logX,["ACTIONS"]);
        self::$initiated = true;
    }

    public static function get(int|DB\code_ownership_log $code_ownership_log, bool $baseOnly = false): DB\code_ownership_log|DB\code_ownership_logX{
        self::init();
        return DataGeneric::get(
            base_class: "code_ownership_log",
            extended_class: "code_ownership_logX",
            dataObj: $code_ownership_log,
            priKey:"id",
            uniKey: "",
            baseOnly: $baseOnly
        );
    }

    static public function addLog(string $action, DB\codes $code, $fromUser, DB\user $toUser, int $time): DB\code_ownership_logX
    {
        if(!in_array($action,DB\code_ownership_logX::$ACTIONS)) Assert::throw("invalid action");
        Assert::isGreaterThan($time,0,"time");

        $prod_name = "";
        if($code->variant_id > 0){
            $variant = DataPackageVariant::get($code->variant_id);
            $prod_name = $variant->package_name;
        }
        else if($code->package_id > 0){
            $variant = DataPackageVariant::getDefault($code->package_id);
            $prod_name = $variant->package_name;
        }
        else if($code->product_id > 0){
            $prod = DataProducts::get($code->product_id);
            $prod_name = $prod->product_name;
        }

        $log = new DB\code_ownership_logX();
        $log->code          = $code->code;
        $log->code_type     = $code->code_type;
        $log->prod_name     = $prod_name;
        $log->action        = $action;
        if(!is_null($fromUser) && !($fromUser instanceof DB\user)){
            Assert::throw("Invalid type of fromUser arg");
        }
        $log->from_userId   = is_null($fromUser) ? 0 : $fromUser->id;
        $log->from_username = is_null($fromUser) ? "" : $fromUser->username;
        $log->to_userId     = $toUser->id;
        $log->to_username   = $toUser->username;
        $log->time_added    = $time;
        $log->save();
        return $log;
    }
}