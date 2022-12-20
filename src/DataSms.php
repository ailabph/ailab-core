<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;
class DataSms
{
    private static bool $initiated = false;

    private static function init()
    {
        if (self::$initiated) return;
        Tools::appClassExist("editsession");
        Tools::appClassExist("sms_queue");
        Tools::appClassExist("sms_queueX");
        self::$initiated = true;
    }

    const PROVIDERS = ["semaphore"=>"semaphore"];
    const STATUS = ["open"=>"o","done"=>"d","error"=>"e"];

    public static function addSmsQueue(string $contact, string $msg, bool $is_priority=false, DB\editsession|null $editsession = null, $type = null): DB\sms_queue
    {
        self::init();
        $sms = new DB\sms_queue();
        $sms->provider = self::PROVIDERS["semaphore"];
        $sms->is_priority = $is_priority ? 1 : 0;
        $sms->number = $contact;
        $sms->message = $msg;
        $sms->status = self::STATUS["open"];
        $sms->timeadded = TimeHelper::getCurrentTime()->getTimestamp();
        if(!empty($type)){
            $sms->type = $type;
        }
        if(!is_null($editsession)){
            if($editsession->datatable == "otp"){
                $sms->type = "otp";
                $sms->otp = $editsession->datatext;
                $sms->token = $editsession->edittoken;
            }
        }
        $sms->save();
        return $sms;
    }
}