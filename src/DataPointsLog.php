<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataPointsLog
{
    private static bool $initialized = false;

    private static function init()
    {
        if(self::$initialized) return;
        $wallet_header = Tools::appClassExist("wallet_header");
        $points_logList = Tools::appClassExist("points_logList");
        $points_log = Tools::appClassExist("points_log");

        $points_logX = Tools::appClassExist("points_logX");
        Tools::checkPropertiesExistInClass($points_logX,[
            "ACTIONS",
            "action",
            "time_added",
            "date_added",
            "period",
            "user_id",
            "account_code",
            "account_rank",
            "level",
            "gen_level",
            "sponsor_level",
            "amount",
            "data_value",
            "data_value_remarks",
            "code_source_owner_user_id",
            "code_source_owner_name",
            "code_source_account_id",
            "product_id",
            "product_tag",
            "variant_id",
            "variant_tag",
            "product_srp",
            "product_dp",
            "bonus_percentage",
            "status",
        ]);
        $account = Tools::appClassExist("account");
        $codes = Tools::appClassExist("codes");

        self::$initialized = true;
    }

    public static function createDefault(
        DB\account $for_account,
        string $action,
        ?DB\codes $from_code = null,
        ?int $current_time = null
    ) : DB\points_log
    {
        self::init();
        if(!in_array($action,DB\points_logX::$ACTIONS)) Assert::throw("invalid action:$action");
        $current_time = $current_time ?? time();

        $new_point = new DB\points_log();
        $new_point->time_added = $current_time;
        $new_point->action = $action;
        $new_point->date_added = TimeHelper::getAsFormat($current_time,TimeHelper::FORMAT_DATE);
        $new_point->user_id = $for_account->user_id;
        $new_point->account_code = $for_account->account_code;
        $new_point->account_rank = $for_account->account_type;
        $new_point->account_id = $for_account->id;
        $new_point->period =
            TimeHelper::getCurrentTime()
                ->startOfMonth()
                ->format(TimeHelper::FORMAT_DATE);
        $new_point->status = "o";

        Tools::importValuesToObject($for_account,$new_point,"pl_");

        if($from_code){
            $from_user = DataUser::get($from_code->used_by);
            $new_point->code_source = $from_code->code;
            $new_point->code_source_owner_name = $from_user->firstname." ".$from_user->lastname;
            $new_point->code_source_owner_user_id = $from_user->id;

            if($from_code->account_id > 0){
                $from_account = DataAccount::get($from_code->account_id);
                $new_point->code_source_account_id = $from_account->id;
                $new_point->level = $from_account->level;
                $new_point->sponsor_level = $from_account->sponsor_level;
                $new_point->gen_level = $from_account->sponsor_level - $for_account->sponsor_level;
                // if($new_point->gen_level < 0) Assert::throw("Invalid gen level:$new_point->gen_level");
            }

            if($from_code->product_id > 0){
                $product = DataProducts::get($from_code->product_id);
                $new_point->product_id = $product->id;
                $new_point->product_tag = $product->product_tag;
                $new_point->product_srp = $product->price;
                $new_point->product_dp = $product->dist_price;
            }
            if($from_code->variant_id > 0){
                $variant = DataPackageVariant::get($from_code->variant_id);
                $new_point->variant_id = $variant->id;
                $new_point->variant_tag = $variant->package_tag;
            }
        }
        return $new_point;
    }

    public static function save(
        DB\points_log $point,
        ?DB\account $account_snapshot = null,
        ?DB\wallet_header $wallet_snapshot = null
    ): DB\points_log
    {
        if($account_snapshot){
            $clean_account = new DB\account();
            $clean_account->loadValues(data:$account_snapshot,isNew:false,strict: true);
            $point->snapshot_account = json_encode($clean_account);
        }
        if($wallet_snapshot){
            $clean_wallet = new DB\wallet_header();
            $clean_account->loadValues(data:$wallet_snapshot,isNew: false,strict: true);
            $point->snapshot_wallet = json_encode($wallet_snapshot);
        }
        $point->save();
        return $point;
    }

    public static function get(
        int $account_id,
        ?string $action = null,
        ?string $order = null,
    ): DB\points_logList
    {
        $where = " WHERE account_id=:account_id ";
        $param[":account_id"] = $account_id;
        if(!empty($action)){
            $where .= " AND action=:action ";
            $param[":action"] = $action;
        }
        return new DB\points_logList($where,$param,$order??"");
    }

    #region ASSERT

    #endregion END ASSERT
}