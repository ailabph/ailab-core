<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataTopUpRequest
{
    private static bool $initiated = false;

    private static function init()
    {
        if (self::$initiated) return;
        Tools::appClassExist("user");
        Tools::appClassExist("top_up_credits_header");
        Tools::appClassExist("top_up_credits_headerList");
        Tools::appClassExist("top_up_credits_detail");
        Tools::appClassExist("top_up_credits_detailList");
        Tools::appClassExist("top_up_request_header");
        Tools::appClassExist("top_up_request_headerList");
        Tools::appClassExist("top_up_request_detail");
        Tools::appClassExist("top_up_request_detailList");
        $top_up_minimum = config::getPublicOption("top_up_minimum");
        if(!empty($top_up_minimum)){
            Assert::isGreaterThan($top_up_minimum,0,"top_up_minimum");
            self::$TOP_UP_MINIMUM = (int)$top_up_minimum;
        }
        self::$initiated = true;
    }

    public static int $TOP_UP_MINIMUM = 10000;

    const PROCESS_STATUS = [
        "pending" => "pending",
        "approved" => "approved",
        "denied" => "denied",
    ];

    #region GETTERS

    public static function get(): DB\top_up_request_header
    {
        self::init();

        $header = new DB\top_up_request_headerList(" WHERE 1 ",[]," LIMIT 1 ");
        $header = $header->fetch();
        if(!$header){
            $header = new DB\top_up_request_header();
            $header->percentage_rate = self::getDefaultPercentageRate();
            $header->total_top_up_approved = 0;
            $header->total_top_up_denied = 0;
            $header->total_credits_in = 0;
            $header->total_credits_used = 0;
            $header->save();
        }
        return $header;
    }

    private static function getDefaultPercentageRate(): float|string{
        $to_return = 0.05;
        $top_up_percentage_rate = config::getPublicOption("top_up_percentage_rate");
        if(!empty($top_up_percentage_rate)){
            Assert::isGreaterThan($top_up_percentage_rate,0,"top up percentage rate config");
            $to_return = $top_up_percentage_rate;
        }
        return $to_return;
    }

    public static function getPercentageRate(): float|string{
        $top_up_header = self::get();
        return $top_up_header->percentage_rate;
    }

    public static function list(string $where = " WHERE 1 ", array $param = [], string $order =""): DB\top_up_request_detailList{
        return new DB\top_up_request_detailList($where,$param,$order);
    }

    public static function detailRecord(int|DB\top_up_request_detail $top_up_request_detail): DB\top_up_request_detail
    {
        if($top_up_request_detail instanceof DB\top_up_request_detail) return $top_up_request_detail;
        $to_return = new DB\top_up_request_detail(["id"=>$top_up_request_detail]);
        if($to_return->isNew()) Assert::throw("record not found for top up request detail with id:$top_up_request_detail");
        return $to_return;
    }

    #endregion END OF GETTERS



    #region CHECKS
    public static function isAuthorizedToAdd(DB\user|int $user, bool $throw = true): bool{
        $user = DataUser::get($user);
        $result = in_array($user->usergroup,["admin","owner"]);
        if(!$result && $throw) Assert::throw("Not authorized");
        return $result;
    }
    public static function isAuthorizedToProcess(DB\user|int $user, bool $throw = true): bool{
        $user = DataUser::get($user);
        $result = $user->usergroup == "admin";
        if(!$result && $throw) Assert::throw("Not authorized");
        return $result;
    }
    #endregion END OF CHECKS



    #region PROCESS

    public static function setPercentageRate(float|string $new_rate, ?int $logged_user = null)
    {
        if(is_null($logged_user)) $logged_user = Session::getCurrentUser()->id;
        Assert::isGreaterThan($new_rate,0,"new rate");
        if($new_rate > 1 || $new_rate < 0.001){
            Assert::throw("unable to set percentage larger than 1 or lesser than 0.001");
        }
        $top_up_header =  self::get();
        $top_up_header->percentage_rate = $new_rate;
        $top_up_header->save();
    }

    public static function computeAmountToCredit(float|int|string $top_up_amount, float|int|string $percentage_rate): string
    {
        Assert::isGreaterThan($top_up_amount,0,"top up amount");
        Assert::isGreaterThan($percentage_rate,0,"percentage rate");
        return bcdiv($top_up_amount,$percentage_rate,2);
    }

    public static function add(array $data, ?int $added_by = null): DB\top_up_request_detail
    {
        Assert::inTransaction();
        self::init();
        if(is_null($added_by)){
            $added_by = tools::getCurrentUser()->id;
        }
        self::isAuthorizedToAdd($added_by);

        // PARSE FORM
        $top_up_amount = Tools::parseKeyArray($data,"top_up_amount");
        $top_up_notes = Tools::parseKeyArray($data,"top_up_notes");
        $top_up_screenshot = Tools::parseKeyArray($data,"top_up_screenshot");

        // CHECK DATA
        Assert::isGreaterThan($top_up_amount);
        if($top_up_amount < self::$TOP_UP_MINIMUM){
            Assert::throw("Top up amount is below the minimum ".number_format(self::$TOP_UP_MINIMUM,2));
        }

        $new_top_up = new DB\top_up_request_detail();
        $new_top_up->top_up_amount = $top_up_amount;
        $new_top_up->percentage_rate = self::getPercentageRate();
        $new_top_up->amount_to_credit = self::computeAmountToCredit($new_top_up->top_up_amount,$new_top_up->percentage_rate);
        $new_top_up->top_up_by = $added_by;
        $new_top_up->time_top_up = time();
        $new_top_up->top_up_notes = $top_up_notes;
        $new_top_up->top_up_screenshot = $top_up_screenshot;
        $new_top_up->process_status = self::PROCESS_STATUS["pending"];
        $new_top_up->save();

        #SMS
        $liza = new DB\user(["username"=>"liza","usergroup"=>"admin"]);
        if(!$liza->isNew()){
            DataSms::addSmsQueue($liza->contact,"Pending System top up request");
        }

        return $new_top_up;
    }

    public static function approve(array $data, ?int $approved_by = null): DB\top_up_request_detail{
        Assert::inTransaction();
        self::init();
        if(is_null($approved_by)) $approved_by = Session::getCurrentUser()->id;
        self::isAuthorizedToProcess($approved_by);

        $detail_id = Tools::parseKeyArray($data,"detail_id");
        $process_remarks = Tools::parseKeyArray($data,"process_remarks");

        $for_approval = self::detailRecord($detail_id);
        if($for_approval->process_status != self::PROCESS_STATUS["pending"]){
            Assert::throw("top up record is not for approval");
        }

        $for_approval->process_status = self::PROCESS_STATUS["approved"];
        $for_approval->time_processed = TimeHelper::getCurrentTime()->getTimestamp();
        $for_approval->process_remarks = $process_remarks;
        $for_approval->processed_by = $approved_by;
        $for_approval->save();

        $header = self::get();
        $header->total_top_up_approved = bcadd($header->total_top_up_approved,$for_approval->top_up_amount,2);
        $header->total_credits_in = bcadd($header->total_credits_in,$for_approval->amount_to_credit,2);
        $header->save();

        DataTopUpCredits::addCredits($for_approval);

        return $for_approval;
    }

    public static function deny(array $data, ?int $denied_by = null): DB\top_up_request_detail
    {
        Assert::inTransaction();
        self::init();
        $denied_by = is_null($denied_by) ? Session::getCurrentUser()->id : $denied_by;
        self::isAuthorizedToProcess($denied_by);

        $detail_id = Tools::parseKeyArray($data,"detail_id");
        $process_remarks = Tools::parseKeyArray($data,"process_remarks");

        $for_deny = self::detailRecord($detail_id);
        if($for_deny->process_status != self::PROCESS_STATUS["pending"]){
            Assert::throw("top up record is not on pending status");
        }

        $for_deny->process_status = self::PROCESS_STATUS["denied"];
        $for_deny->time_processed = TimeHelper::getCurrentTime()->getTimestamp();
        $for_deny->process_remarks = $process_remarks;
        $for_deny->processed_by = $denied_by;
        $for_deny->save();

        return $for_deny;
    }

    #endregion END OF PROCESS

}