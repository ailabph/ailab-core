<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;
use PhpParser\Node\Expr\AssignOp\ShiftLeft;

class DataCodes implements Loggable
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $order_header = Tools::appClassExist("order_header");
        $payment = Tools::appClassExist("payment");
        $package_variant = Tools::appClassExist("package_variant");
        $codesX = Tools::appClassExist("codesX");
        Tools::checkPropertiesExistInClass($codesX,[

        ]);

        self::$initiated = true;
    }
    public static function get(DB\codes|string|int $codes,bool $baseOnly = false, bool $throw = true): DB\codesX|DB\codes|false{
        self::init();
        return DataGeneric::get(
            base_class: "codes",
            extended_class: "codesX",
            dataObj: $codes,
            priKey: "id",
            uniKey:"code",
            baseOnly:$baseOnly,
            throw: $throw
        );
    }

    public static function getProductCodes(int|string|DB\user $owner, bool $unused = true): DB\codesList{
        $owner = DataUser::get($owner);
        return new DB\codesList(
            " WHERE owned_by=:user_id AND status=:status ",
            [":user_id"=>$owner->id,":status"=>($unused?"o":"c")]);
    }

    #region ENTRY
    public static function createNewEntryCodes(
        int|string|DB\package_variant $variant,
        int $qty,
        bool $is_auto_generated = false,
        bool $is_bundle = false,
        ...$args
    ): DB\codesList
    {
        Assert::inTransaction();
        $variant = DataPackageVariant::get($variant);
        Assert::isGreaterThan($qty,0,"qty");

        /** @var DB\payment $payment */
        $payment = DataGeneric::getDataObjectsFromArray($args,"payment");
        /** @var DB\order_header $order_header */
        $order_header = DataGeneric::getDataObjectsFromArray($args,"order_header");
        /** @var DB\order_detail $order_detail */
        $order_detail = DataGeneric::getDataObjectsFromArray($args,"order_detail");
        /** @var DB\user $owner */
        $owner = DataGeneric::getDataObjectsFromArray($args,"user");

        self::hasGeneratedCodeFromPaymentOrCenterRelease(payment: $payment,order_header: $order_header,variant_id: $variant->id);

        if(!$owner){
            $owner = Session::getCurrentUser(throw:true);
        }

        $new_codes = new DB\codesList(" WHERE 0 ",[]);
        for($x=0;$x<$qty;$x++){
            $code = self::createNewEntryCode($variant,$owner,$payment,$order_header,$order_detail);
            self::checkCodeIntegrity($code);
            $code->save();
            $user_from = $order_header ? DataUser::get($order_header->user_id) : null;
            DataCodeOwnershipLog::addLog(DB\code_ownership_logX::$ACTIONS["purchase"],$code,$user_from,$owner,$code->time_purchased > 0 ? $code->time_purchased : $code->time_generated);
            $new_codes->list[] = $code;
        }
        //endregion
        return $new_codes;
    }

    public static function createNewEntryCode(
        DB\package_variant $variant,
        ...$args,
    ):DB\codesX{
        $owner = null;
        $payment = null;
        $order_header = null;
        $order_detail = null;
        $approved_by_user = null;
        foreach ($args as $arg){
            if($arg instanceof DB\user){
                $owner = $arg;
            }
            else if($arg instanceof DB\payment){
                $payment = $arg;
            }
            else if($arg instanceof DB\order_header){
                $order_header = $arg;
            }
            else if($arg instanceof DB\order_detail){
                $order_detail = $arg;
            }
        }

        $new_code = new DB\codesX();
        if($payment){
            $owner = DataUser::get($payment->created_by);
            $approved_by_user = DataUser::get($payment->approved_by);

            $new_code->payment_ref = $payment->payment_referrence;
            $new_code->time_purchased = $payment->time_approved;
            if($payment->ne_order > 0){
                $new_code->ne_order_id = $payment->ne_order;
            }
        }
        else if($order_header){
            if(is_null($order_detail) && !($order_detail instanceof DB\order_detail)){
                Assert::throw(error_message:"order_detail is required",critical_error: true);
            }
            $approved_by_user = DataUser::get($order_header->user_id);
            $owner = $approved_by_user;
            if($order_header->order_for > 0){
                $owner = DataUser::get($order_header->order_for);
            }
            $new_code->order_id = $order_header->id;
            $new_code->order_detail = $order_detail->id;
        }
        else if($owner){
            $approved_by_user = $owner;
        }
        else{
            $owner = Session::getCurrentUser(true);
            $approved_by_user = $owner;
        }

        $new_code->code_type = "e";
        $new_code->package_id = $variant->package_id;
        $new_code->variant_id = $variant->id;
        $new_code->variant_tag = $variant->package_tag;
        $new_code->code = self::generateAccountNumber();
        $new_code->pin = Random::getRandomInt(1000,9999);
        $new_code->time_generated = TimeHelper::getCurrentTime()->getTimestamp();
        $new_code->status = "o";
        $new_code->purchased_by = $owner->id;
        $new_code->owned_by = $owner->id;
        $new_code->approved_by = $approved_by_user->id;
        $new_code->save();
        return $new_code;
    }
    #endregion END ENTRY


    #region PRODUCTS
    public static function createNewProductCodes(
        int|string|DB\products $product,
        int $qty,
        ...$args
    ): DB\codesList
    {
        Assert::inTransaction();
        $product = DataProducts::get($product);
        Assert::isGreaterThan($qty,0,"qty");

        /** @var DB\payment $payment */
        $payment = DataGeneric::getDataObjectsFromArray($args,"payment");
        /** @var DB\payment_details $payment_details */
        $payment_details = DataGeneric::getDataObjectsFromArray($args,"payment_details");
        /** @var DB\order_header $order_header */
        $order_header = DataGeneric::getDataObjectsFromArray($args,"order_header");
        /** @var DB\order_detail $order_detail */
        $order_detail = DataGeneric::getDataObjectsFromArray($args,"order_detail");
        /** @var DB\user $owner */
        $owner = DataGeneric::getDataObjectsFromArray($args,"user");

        self::hasGeneratedCodeFromPaymentOrCenterRelease(payment: $payment,order_header: $order_header,product_id:$product->id);

        if(!$owner){
            $owner = Session::getCurrentUser(throw:true);
        }

        $new_codes = new DB\codesList(" WHERE 0 ",[]);
        for($x=0;$x<$qty;$x++){
            $code = self::createNewProductCode($product,$owner,$payment,$payment_details,$order_header,$order_detail);
            self::checkCodeIntegrity($code);
            $code->save();
            $user_from = $order_header ? DataUser::get($order_header->user_id) : null;
            DataCodeOwnershipLog::addLog(DB\code_ownership_logX::$ACTIONS["purchase"],$code,$user_from,$owner,$code->time_purchased > 0 ? $code->time_purchased : $code->time_generated);
            $new_codes->list[] = $code;
        }
        //endregion
        return $new_codes;
    }

    public static function createNewProductCode(
        DB\products $product,
       ...$args,
    ):DB\codesX{
        /** @var DB\user $owner */
        $owner = DataGeneric::getDataObjectsFromArray($args,"user");
        /** @var DB\payment $payment */
        $payment = DataGeneric::getDataObjectsFromArray($args,"payment");
        /** @var DB\payment_details $payment_detail */
        $payment_detail = DataGeneric::getDataObjectsFromArray($args,"payment_details");
        /** @var DB\order_header $order_header */
        $order_header = DataGeneric::getDataObjectsFromArray($args,"order_header");
        /** @var DB\order_detail $order_detail */
        $order_detail = DataGeneric::getDataObjectsFromArray($args,"order_detail");
        $approved_by_user = null;

        $new_code = new DB\codesX();
        if($payment){
            if(!$payment_detail) Assert::throw(error_message: "payment_detail is required",critical_error: true);
            $owner = DataUser::get($payment->created_by);
            $approved_by_user = DataUser::get($payment->approved_by);

            $new_code->payment_ref = $payment->payment_referrence;
            $new_code->time_purchased = $payment->time_approved;
            $new_code->price_paid = $payment_detail->unit_price;
            if($payment->ne_order > 0){
                $new_code->ne_order_id = $payment->ne_order;
            }
        }
        else if($order_header){
            if(is_null($order_detail) && !($order_detail instanceof DB\order_detail)){
                Assert::throw(error_message:"order_detail is required",critical_error: true);
            }
            $approved_by_user = DataUser::get($order_header->user_id);
            $owner = $approved_by_user;
            if($order_header->order_for > 0){
                $owner = DataUser::get($order_header->order_for);
            }
            $new_code->order_id = $order_header->id;
            $new_code->order_detail = $order_detail->id;
        }
        else if($owner){
            $approved_by_user = $owner;
        }
        else{
            $owner = Session::getCurrentUser(true);
            $approved_by_user = $owner;
        }

        $new_code->code_type = "p";
        $new_code->product_id = $product->id;
        $new_code->product_tag = $product->product_tag;
        $new_code->code = self::generateProductCode($product);
        $new_code->pin = Random::getRandomInt(1000,9999);
        $new_code->time_generated = TimeHelper::getCurrentTime()->getTimestamp();
        $new_code->status = "o";
        $new_code->purchased_by = $owner->id;
        $new_code->owned_by = $owner->id;
        $new_code->approved_by = $approved_by_user->id;
        $new_code->save();
        return $new_code;
    }
    #endregion END PRODUCTS


    #region CHECKS

    private static function hasGeneratedCodeFromPaymentOrCenterRelease(
        int|string|DB\payment|false $payment,
        int|string|DB\order_header|false $order_header,
        int|null $variant_id = null,
        int|null $product_id = null,
    ): void
    {
        if(is_null($variant_id) && is_null($product_id)){
            Assert::throw(error_message:"unable to check if code has been generated, no variant or prod id given",critical_error: true);
        }
        if($payment){
            $payment = DataPayment::get($payment);
            if($payment->ne_order > 0){
                $checkCodes = new DB\codesList(
                    " WHERE ne_order_id=:order_id  "
                    ,[":order_id"=>$payment->ne_order]);
                if($checkCodes->count() > 0){
                    Assert::throw(error_message:"Code already generated for ne_order",critical_error: true);
                }
            }
            else{
                $where = " WHERE payment_ref=:ref ";
                $param[":ref"] = $payment->payment_referrence;
                if($variant_id > 0){
                    $where .= " AND variant_id=:variant_id ";
                    $param[":variant_id"] = $variant_id;
                }
                if($product_id > 0){
                    $where .= " AND product_id=:product_id ";
                    $param[":product_id"] = $product_id;
                }
                $checkCodes = new DB\codesList($where,$param);
                if($checkCodes->count() > 0){
                    Assert::throw(error_message: "Code already generated for payment_ref:$payment->payment_referrence",critical_error: true);
                }
            }
        }
        if($order_header){
            $order_header = DataOrderHeader::get($order_header);
            $checkCodes = new DB\codesList(
                " WHERE order_id=:order_id "
                ,[":order_id"=>$order_header->id]);
            if($checkCodes->count() > 0){
                Assert::throw("code already generated for this order_header");
            }
        }
    }

    private static function checkCodeIntegrity(DB\codes $code): void
    {
        if(empty($code->product_id) && empty($code->variant_id))
            Assert::throw(error_message:"no product_id and variant_id",critical_error: true);
        if($code->variant_id > 0){
            if(empty($code->package_id)) Assert::throw(error_message:"no package_id",critical_error: true);
            if(empty($code->variant_tag)) Assert::throw(error_message: "no variant_tag",critical_error: true);
            if($code->code_type != "e") Assert::throw(error_message: "expected code_type e",critical_error: true);
        }
        if($code->product_id > 0){
            if(empty($code->product_tag)) Assert::throw(error_message:"product_tag missing",critical_error: true);
        }
    }

    public static function isProductCode(DB\codes $code, bool $throw = false):bool {
        if($code->code_type == "p") return true;
        else if($throw) Assert::throw("code is not of product type");
        return false;
    }

    public static function isEntryCode(DB\codes $code, bool $throw = false): bool{
        if($code->code_type == "e") return false;
        else if($throw) Assert::throw("code is not of entry type");
        return false;
    }

    #endregion CHECKS


    #region PROCESS

    # TODO: for implementation
    static public function useProduceCode(DB\account $for_account, DB\codes $prod_code){}
    # TODO: for implementation
    static public function transfer(DB\codes $code, DB\user|string|int $to_user){}

    #endregion END OF PROCESS


    #region UTILITIES
    public static function generateAccountNumber(): string{
        do{
            $accountNumber = Random::getRandomStr(length:9,options:Random::OPTION_ALPHA_SAFE_HIGH);
            $code = self::get(codes:$accountNumber,throw:false);
        }while($code);
        return $accountNumber;
    }
    public static function generateProductCode(DB\products $product): string{
        Assert::recordExist($product);
        do{
            $left = "P" . str_pad($product->id, 2, '0', STR_PAD_LEFT);
            $productNumber = $left . "-" . Random::getRandomStr(length:9,options: Random::OPTION_ALPHA_SAFE_HIGH);
            $code = DataCodes::get(codes:$productNumber,baseOnly:true,throw:false);
        }while($code);
        return $productNumber;
    }
    static function addLog(string $log, int $line)
    {
        Logger::add(msg:$log,category: "codes",line:$line);
    }
    #endregion END UTILITIES
}