<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;

class DataAccount
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $account = Tools::appClassExist("account");
        $accountX = Tools::appClassExist("accountX");
        self::$initiated = true;
    }

    #region GETTERS

    public static function get(DB\account|string|int $account, bool $baseOnly = false): DB\accountX|DB\account{
        self::init();
        return DataGeneric::get("account","accountX",$account,"id","account_code",$baseOnly);

    }

    public static function getSponsorUpline(DB\account|string|int $account): DB\accountX|false{
        $account = self::get($account);
        if(!empty($account->sponsor_account_id) && !($account->sponsor_id > 0)){
            Assert::throw(
                "account:".$account->account_code
                ." has sponsor code info:".$account->sponsor_account_id
                ." but has not sponsor_id");
        }
        if(empty($account->sponsor_account_id)) return false;
        $sponsor = self::get($account->sponsor_id);
        if($sponsor->id != $account->sponsor_id) Assert::throw("sponsor id do not match");
        return $sponsor;
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

    #endregion END OF GETTERS

    #region CHECKS
    public static function checkIntegrityAndSave(DB\account &$account){
        if($account->sponsor_id < 0 && empty($account->sponsor_account_id)){
            $account->sponsor_id = 0;
        }
        if($account->placement_id < 0 && empty($account->placement_account_id)){
            $account->placement_id = 0;
        }
        $account->save();
    }
    #endregion

    #region PROCESS

    # TODO: for implementation
    public static function encode(DB\user $user, DB\codes $entry_code, $placement, $position, $sponsor): DB\account{
        // hook before encode
        // hook after encode
    }
    # TODO: for implementation
    public static function upgrade(DB\account $account, DB\codes $upgrade_code): account{
        // hook before upgrade
        // hook after upgrade
    }

    #endregion END OF PROCESS
}