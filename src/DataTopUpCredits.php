<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;
use PhpParser\Node\Expr\AssignOp\ShiftLeft;

class DataTopUpCredits
{
    private static bool $initiated = false;

    private static function init()
    {
        if (self::$initiated) return;
        Tools::appClassExist("top_up_credits_header");
        Tools::appClassExist("top_up_credits_headerList");
        Tools::appClassExist("top_up_credits_detail");
        Tools::appClassExist("payment");
        self::$initiated = true;
    }

    const PROCESS_STATUS = [
        "pending" => "pending",
        "approved" => "approved",
        "denied" => "denied",
    ];

    public static bool|null $OVERRIDE_TOP_UP_ENABLE = null;

    #region GETTERS
    public static function topUpSystemEnabled(): bool
    {
        if(is_bool(self::$OVERRIDE_TOP_UP_ENABLE)) return self::$OVERRIDE_TOP_UP_ENABLE;
        $to_return = true; // default
        $from_config = Config::getPublicOption("top_up_system_enable");
        if($from_config == "y"){
            $to_return = true;
        }
        if($from_config == "n"){
            $to_return = false;
        }
        return $to_return;
    }

    public static function get():DB\top_up_credits_header{
        self::init();
        $header = new DB\top_up_credits_headerList(" WHERE 1 ",[]);
        $header = $header->fetch();
        if(!$header){
            $header = new DB\top_up_credits_header();
            $header->total_credits_in = 0;
            $header->total_credits_out = 0;
            $header->total_credits_balance = 0;
            $header->status = "o";
            $header->save();
        }
        return $header;
    }
    public static function currentBalance(): string
    {
        $current_balance = "0";
        $all_credit_details = new DB\top_up_credits_detailList(" WHERE 1 ",[]);
        while($detail = $all_credit_details->fetch()){
            if($detail->credits_in > 0){
                $current_balance = bcadd($current_balance,$detail->credits_in,2);
            }
            if($detail->credits_out > 0){
                $current_balance = bcsub($current_balance,$detail->credits_out,2);
            }
        }
        return $current_balance;
    }
    #endregion END OF GETTERS


    public static function addCredits(DB\top_up_request_detail $detail): array{
        Assert::inTransaction();
        $header = self::get();
        if($detail->process_status != self::PROCESS_STATUS["approved"]){
            Assert::throw("unable to add credits, detail request is not approved");
        }

        $check_if_added = new DB\top_up_credits_detail(["request_detail_in"=>$detail->id]);
        if(!$check_if_added->isNew()){
            Assert::throw("credits already added for this request detail");
        }

        $current_balance = self::currentBalance();

        $new_credit_detail = new DB\top_up_credits_detail();
        $new_credit_detail->credits_in = $detail->amount_to_credit;
        $new_credit_detail->request_detail_in = $detail->id;
        $new_credit_detail->running_credit_balance = bcadd($current_balance,$detail->amount_to_credit,2);
        $new_credit_detail->time_added = TimeHelper::getCurrentTime()->getTimestamp();
        $new_credit_detail->status = "o";
        $new_credit_detail->save();

        $header->total_credits_in = bcadd($header->total_credits_in,$detail->amount_to_credit,2);
        $header->total_credits_balance = bcsub($header->total_credits_in,$header->total_credits_out,2);
        $header->save();

        if($header->total_credits_balance != $new_credit_detail->running_credit_balance){
            Assert::throw("something is wrong, header credit balance($header->total_credits_balance) is not the same as computed current balance($new_credit_detail->running_credit_balance)");
        }

        return [
            "header" => $header,
            "detail" => $new_credit_detail
        ];
    }

    public static function useCredits(DB\payment $payment): array | false
    {
        Assert::inTransaction();
        if($payment->isNew()){
            Assert::throw("payment record is not in db");
        }
        if($payment->status != DB\paymentX::$STATUS["status_approved"]){
            Assert::throw("unable to use credits if payment is not approved");
        }
        if($payment->payment_mode == "cd" || $payment->payment_mode == "fs") return false;
        $header = self::get();
        if($payment->amount > $header->total_credits_balance){
            Assert::throw("not enough credits");
        }


        $current_balance = self::currentBalance();

        $new_detail = new DB\top_up_credits_detail();
        $new_detail->credits_out = $payment->amount;
        $new_detail->payment_id = $payment->id;
        $new_detail->running_credit_balance = bcsub($current_balance,$payment->amount,2);
        $new_detail->time_added = TimeHelper::getCurrentTime()->getTimestamp();
        $new_detail->status = "o";
        $new_detail->save();

        $header->total_credits_out = bcadd($header->total_credits_out,$new_detail->credits_out,2);
        $header->total_credits_balance = bcsub($header->total_credits_in,$header->total_credits_out,2);
        $header->save();

        if($header->total_credits_balance != $new_detail->running_credit_balance){
            Assert::throw("something is wrong, header credit balance($header->total_credits_balance) is not the same as computed current balance($new_detail->running_credit_balance)");
        }

        return [
            "header" => $header,
            "detail" => $new_detail
        ];
    }
}