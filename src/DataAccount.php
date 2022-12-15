<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;

class DataAccount
{
    public static function get(DB\account|string|int $account): DB\accountX{
        $to_return = new DB\accountX();
        $get_method = "";

        if($account instanceof DB\accountX){
            $get_method = ",via passed accountX object";
            $to_return = $account;
        }
        else if($account instanceof DB\account){
            $get_method = ", via conversion to accountX object, id:$account->id";
            $to_return = self::get($account->id);
        }

        if($to_return->isNew() && is_numeric($account)){
            $get_method = ", via account id:$account";
            $to_return = new DB\accountX(["id"=>$account]);
        }

        if($to_return->isNew() && is_string($account)){
            $get_method = ", via account_code:$account";
            $to_return = new DB\accountX(["account_code"=>$account]);
        }

        if($to_return->isNew()){
            Assert::throw("account record not retrieved from database".$get_method);
        }

        return $to_return;
    }

    public static function getSponsorUpline(DB\account|string|int $account): DB\accountX|false{
        $account = self::get($account);
        if(!empty($account->sponsor_account_id) && !($account->sponsor_id > 0)){
            Assert::throw(
                "account:".$account->account_code
                ." has sponsor code info:".$account->sponsor_account_id
                ." but has not sponsor_id");
        }
        if(empty($account->sponsor_id)) return false;
        return self::get($account->sponsor_id);
    }

    public static function getBinaryUpline(DB\account|string|int $account): DB\accountX|false{
        $account = self::get($account);
        if(!empty($account->placement_account_id) && !($account->placement_id > 0)){
            Assert::throw(
                "account:".$account->account_code
                ." has placement code info:".$account->placement_account_id
                ." but has not placement_id");
        }
        if(empty($account->placement_id)) return false;
        return self::get($account->placement_id);
    }
}