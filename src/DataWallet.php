<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataWallet
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $wallet_header = Tools::appClassExist("wallet_header");
        $user = Tools::appClassExist("user");
        self::$initiated = true;
    }

    public static function get(int|string|DB\user $user): DB\wallet_header{
        self::init();
        $user = DataGeneric::get("user","userX",$user,"id","username",true,true);
        $wallet_header = new DB\wallet_header(["user_id"=>$user->id]);
        if($wallet_header->isNew()){
            $wallet_header->balance = 0;
            $wallet_header->status = "o";
            $wallet_header->save();
        }
        return $wallet_header;
    }
}