<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataWallet
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $account = Tools::appClassExist("wallet");
        self::$initiated = true;
    }

    public static function get(int|DB\wallet_header $wallet): DB\wallet_header{
        self::init();
        return DataGeneric::get("wallet_header","",$wallet,"user_id","",true,true);
    }
}