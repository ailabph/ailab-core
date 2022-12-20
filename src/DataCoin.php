<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataCoin
{
    private static bool $initiated = false;

    private static function init()
    {
        if (self::$initiated) return;
        $coin_detail = Tools::appClassExist("coin_detail");
        $coin_detailList = Tools::appClassExist("coin_detailList");
        $coin_header = Tools::appClassExist("coin_header");
        $coin_headerX = Tools::appClassExist("coin_headerX");
        $payment = Tools::appClassExist("payment");
        $payment_details = Tools::appClassExist("payment_details");
        $payment_detailsX = Tools::appClassExist("payment_detailsX");
        Tools::checkPropertiesExistInClass($payment_detailsX, [
            "ITEM_TYPES",
        ]);
        Tools::checkPropertiesExistInClass($payment, [
            "PURCHASE_TYPE",
        ]);

        self::$initiated = true;
    }

    public static function get(null|int|string|DB\user $user = null): DB\coin_header{
        Assert::inTransaction();
        if(is_null($user)){
            $user = Session::getCurrentUser();
        }
        $user = DataUser::get($user);
        $lockWallet = new DB\coin_headerList(" WHERE user_id=:user_id ",[":user_id"=>$user->id]," FOR UPDATE ");
        if($lockWallet->count() == 0){
            $newWallet = new DB\coin_header();
            $newWallet->user_id = $user->id;
            $newWallet->status = "o";
            $newWallet->amount = 0;
            $newWallet->last_update = TimeHelper::getCurrentTime()->getTimestamp();
            $newWallet->save();
            return $newWallet;
        }
        return $lockWallet->fetch();
    }

    public static function addFromPurchase(DB\payment $payment, null|DB\payment_details $detail = null): DB\coin_header|bool
    {
        Assert::inTransaction();
        if ($payment->status != DB\paymentX::$STATUS["status_approved"]) {
            Assert::throw("Cannot generate center credits if payment is not yet approved");
        }

        $source = "";
        $ref = "";
        $totalCoinPurchase = 0;
        $centerOwner = DataUser::get($payment->created_by);

        #region SET COIN TO ADD

        if($payment->purchase_type == DB\paymentX::$PURCHASE_TYPE["ne_order"]){
            if(is_null($detail)){
                Assert::throw(error_message:"required detail to proceed to generate coin from woocommerce",critical_error: true);
            }
            if($detail->item_type != DB\payment_detailsX::$ITEM_TYPES["ecoin"]){
                Assert::throw(error_message:"Invalid item_type:$detail->item_type",critical_error: true);
            }
            Assert::isGreaterThan($detail->total_amount,0,"detail->total_amount");
            $totalCoinPurchase = $detail->total_amount;

            if(empty($payment->ne_order)){
                Assert::throw("Expects an e-commerce ORDER Id for purchase_type $payment->purchase_type");
            }
            $ref = $payment->ne_order;
            $source = "ne_order";
        }
        else if($payment->purchase_type == DB\paymentX::$PURCHASE_TYPE["ecoin"]){
            Assert::isGreaterThan($payment->amount,0,"payment->amount");
            $totalCoinPurchase = $payment->amount;
            $ref = $payment->payment_referrence;
            $source = "buy";
        }
        else{
            Assert::throw(error_message:"Payment purchase_type:$payment->purchase_type not implemented for center credits purchase",critical_error: true);
        }

        #endregion

        // CHECK IF DUPLICATE
        $checkCoinDetail = new DB\coin_detailList(" WHERE ref=:ref ",[":ref"=>$ref]);
        if($checkCoinDetail->count() > 0){
            $error_message = "Abort generating Center Credits for Ref:$ref.";
            $error_message .= " Already generated, found:" . $checkCoinDetail->count();
            Assert::throw(error_message:$error_message,critical_error: true);
        }

        $coinWallet = self::get($centerOwner);
        $coinWallet->amount = bcadd($coinWallet->amount, $totalCoinPurchase, 2);
        $coinWallet->last_update = time();
        $coinWallet->save();

        $coinDetail = new DB\coin_detail();
        $coinDetail->amount = $totalCoinPurchase;
        $coinDetail->status = "o";
        $coinDetail->coin_header = $coinWallet->id;
        $coinDetail->user_id = $centerOwner->id;
        $coinDetail->type = "in";
        $coinDetail->source = $source;
        $coinDetail->ref = $ref;
        $coinDetail->time_added = time();
        $coinDetail->save();

        return $coinWallet;
    }
}