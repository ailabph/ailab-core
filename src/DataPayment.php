<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;
use RandomLib\Source\Rand;

class DataPayment implements Loggable
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;

        $user = Tools::appClassExist("user");
        $userX = Tools::appClassExist("userX");
        $payment = Tools::appClassExist("payment");
        $paymentX = Tools::appClassExist("paymentX");
        Tools::checkPropertiesExistInClass($paymentX,[
            "STATUS",
            "PAYMENT_MODES",
            "PURCHASE_TYPE",
            "PURCHASE_TYPE",
            "details",
            "codes",
        ]);

        $inventory_orderX = Tools::appClassExist("inventory_orderX");
        Tools::checkPropertiesExistInClass($inventory_orderX,[
            "::ORDER_TYPES",
            "STATUS_ENUM",
            "PAYMENT_OPTION_ENUM",
            "details",
        ]);

        self::$initiated = true;
    }

    public static float $PRICE_DISCOUNT_OVERRIDE = 0;

    #region GETTERS
    static public function get(int|string|DB\payment $payment, bool $baseOnly = false): DB\paymentX|DB\payment{
        self::init();
        return DataGeneric::get(
            base_class: "payment",
            extended_class: "paymentX",
            dataObj: $payment,
            priKey: "id",
            uniKey: "payment_referrence",
            baseOnly: $baseOnly
        );
    }
    public static function loadPaymentDetails(int|string|DB\payment $payment): DB\paymentX{
        self::init();
        $payment = self::get($payment);
        $payment->details = new DB\payment_detailsList(" WHERE payment_id=:payment_id ",[":payment_id"=>$payment->id]);
        return $payment;
    }
    #endregion END GETTERS

    #region ACTIONS


    static public function create(
        string $purchase_type,
        string $payment_mode,
        string|float $amount,
        int|string|null $created_by = null,
        string|int $time_created = 0,
        array|object $data = [],
    ): DB\paymentX
    {
        Assert::inTransaction();
        if($created_by) $created_by = DataUser::get($created_by)->id;
        else $created_by = Session::getCurrentUser(true)->id;

        $time_created =
            $time_created == 0 ?
                TimeHelper::getCurrentTime() :
                TimeHelper::getTimeAsCarbon($time_created);

        $newPayment = new DB\paymentX();
        $newPayment->loadValues(data:$data,isNew: true);
        $newPayment->payment_referrence = self::generatePaymentReference();
        self::assertValidPurchaseType($purchase_type);
        $newPayment->purchase_type = $purchase_type;
        self::assertValidPaymentMode($payment_mode);
        $newPayment->payment_mode = $payment_mode;
        $newPayment->amount = $amount;
        $newPayment->created_by = $created_by;
        $newPayment->time_created = $time_created->getTimestamp();
        $newPayment->time_payment = $time_created->getTimestamp();
        $newPayment->status = "n";

        if (is_string($newPayment->time_payment)) {
            $newPayment->time_payment = TimeHelper::getTimeAsCarbon($newPayment->time_payment)->getTimestamp();
        }
        self::checkNewPayment($newPayment);
        $newPayment->save();
        return $newPayment;
    }

    static public function createFromForm(array $form_data, int|string|null $created_by = null, int $time_created = 0): DB\paymentX
    {
        Assert::inTransaction();
        if($created_by) $created_by = DataUser::get($created_by)->id;
        else $created_by = Session::getCurrentUser(true)->id;
        $time_created = $time_created > 0 ? $time_created : TimeHelper::getCurrentTime()->getTimestamp();

        $paymentHeader = Tools::parseKeyArray($form_data,"paymentHeader");
        $paymentHeaderData = Tools::parseJson($paymentHeader);

        $payment_mode = Tools::parsePropertyFromObject($paymentHeaderData,"payment_mode");
        $purchase_type = Tools::parsePropertyFromObject($paymentHeaderData, "purchase_type");
        $payment = self::create(
            purchase_type: $purchase_type,
            payment_mode: $payment_mode,
            amount: 0,
            created_by: $created_by,
            time_created: $time_created,
            data: $paymentHeaderData);
        // TEMPORARY DISABLE
        // DataImageLog::updateImageRefId($payment->screenshot,$payment->id);
        return $payment;
    }

    static public function createEntryPayment(
        DB\paymentX $paymentHeader,
        array $paymentDetails
    ) : DB\paymentX
    {
        Assert::inTransaction();
        if ($paymentHeader->isNew())
            Assert::throw(error_message:"payment must be saved on record first",critical_error: true);
        self::assertValidPurchaseType($paymentHeader->purchase_type);

        $paymentHeader->details = new DB\payment_detailsList(" WHERE 0 ", []);
        foreach ($paymentDetails as $payment_detail){
            $package_id = Tools::parsePropertyFromObject($payment_detail,"package_id");
            $qty = Tools::parsePropertyFromObject($payment_detail,"qty");
            $variant_id = Tools::parsePropertyFromObject($payment_detail,"variant_id");

            $package = DataPackage::get($package_id);
            $variant = DataPackageVariant::get($variant_id);

            $det = new DB\payment_details();
            $det->payment_id = $paymentHeader->id;
            $det->item_type = "e";
            $det->package_id = $package->id;
            $det->variant_id = $variant->id;
            $det->item_name = $package->package_name;
            $det->qty = $qty;
            $det->unit_price = $variant->price;
            $det->total_amount = bcmul($det->qty, $det->unit_price, 2);
            $det->status = "o";
            $det->save();
            $paymentHeader->details->list[] = $det;
            $paymentHeader->amount = bcadd($paymentHeader->amount,$det->total_amount,2);
        }
        $paymentHeader->save();
        return $paymentHeader;
    }

    public static function createProductPayment(DB\paymentX $payment, array $paymentDetails): DB\paymentX
    {
        Assert::inTransaction();
        Assert::recordExist($payment);
        if ($payment->purchase_type != DB\paymentX::$PURCHASE_TYPE["product"])
            Assert::throw(error_message: "payment header is not product type",critical_error: true);

        $payment->details = new DB\payment_detailsList(" WHERE 0 ", []);
        foreach ($paymentDetails as $detail){
            $product_id = Tools::parsePropertyFromObject($detail,"prodid",false);
            if(!$product_id) $product_id = Tools::parsePropertyFromObject($detail,"prod_id",true);
            if(!$product_id) $product_id = Tools::parsePropertyFromObject($detail,"product_id");
            $qty = Tools::parsePropertyFromObject($detail,"qty");

            $product = DataProducts::get($product_id,false);

            $det = new DB\payment_details();
            $det->payment_id = $payment->id;
            $det->item_type = $payment->purchase_type;
            $det->product_id = $product->id;
            $det->item_name = $product->product_name;
            $det->qty = $qty;
            $det->unit_price = (double)$product->dist_price;
            $det->total_amount = bcmul($det->qty, $det->unit_price, 2);
            $det->status = "o";
            $det->save();
            $payment->details->list[] = $det;
            $payment->amount = bcadd($payment->amount, $det->total_amount, 2);
        }

        $payment->save();
        return $payment;
    }

    public static function createInventoryEntryPayment(
        DB\paymentX $payment,
        int|string|DB\inventory_order $inventory_order,
        ?int $user_id
    ): DB\paymentX
    {
        Assert::inTransaction();
        if (!isset($user_id)) {
            $user_id = tools::getCurrentUser()->id;
        }
        if ($payment->isNew()) Assert::throw("payment must be saved on record first");
        if ($payment->purchase_type != DB\paymentX::$PURCHASE_TYPE["inventory_entry"]) Assert::throw("payment header is not inventory entry type");

        $inventory_order = DataInventoryOrder::get($inventory_order);
        if ($inventory_order->status != DB\inventory_orderX::$STATUS_ENUM["status_pending"]) {
            Assert::throw("unable to create payment for a non pending inventory order");
        }

        $payment->details = new DB\payment_detailsList(" WHERE 0 ", []);
        $inventory_order->loadDetails();
        foreach ($inventory_order->details as $detail){
            $det = new DB\payment_details();
            $det->payment_id = $payment->id;
            $det->item_type = $payment->purchase_type;
            $det->variant_id = $detail->variant_id;
            $det->item_name = $detail->variant_name;
            $det->qty = $detail->qty;
            $det->unit_price = $detail->price;
            $det->total_amount = $detail->subtotal;
            $det->status = "o";
            $det->save();
            $payment->details->list[] = $det;
            $payment->amount = bcadd($payment->amount, $det->total_amount, 2);
        }

        if ($payment->amount != $inventory_order->total) {
            Assert::throw("payment amount($payment->amount) and inventory order total($inventory_order->total) not equal");
        }
        $inventory_order->order_ref = "PO" . strtoupper($inventory_order->order_type) . TimeHelper::getTimeAsCarbon(time())->format("Ymd") . "-" . Random::getRandomInt(100000, 999999);
        $inventory_order->payment_ref = $payment->payment_referrence;
        $inventory_order->status = DB\inventory_orderX::$STATUS_ENUM["status_new"];
        $inventory_order->save();

        $payment->inventory_order_id = $inventory_order->id;
        $payment->inventory_order_ref = $inventory_order->order_ref;
        $payment->save();

        return $payment;
    }

    static public function approvePayment(
        DB\payment|string|int $payment,
        string $notes = "",
        ?int $staff_id = null,
        ?int $current_time = null
    ): DB\paymentX
    {
        Assert::inTransaction();
        $staff_id = $staff_id ?? Session::getCurrentUser(true)->id;
        $current_time = $current_time ?? TimeHelper::getCurrentTime()->getTimestamp();

        Permissions::section("can_approvepayment",$staff_id);
        $payment = self::get($payment);
        if (empty($payment->details)) {
            $payment = self::loadPaymentDetails($payment);
        }

        if ($payment->status != DB\paymentX::$STATUS["status_new"]) {
            Assert::throw("unable to approve payment status that is not new");
        }

        $payment->approved_by = $staff_id;
        $payment->time_approved = $current_time;
        $payment->approver_notes = $notes;
        $payment->status = DB\paymentX::$STATUS["status_approved"];
        if (empty($payment->time_payment)) {
            $payment->time_payment = $current_time;
        }
        $payment->save();

        $payment->codes = new DB\codesList(" WHERE 0 ", []);
        $processed_payment = false;
        # Generate Entry or Product Codes
        foreach ($payment->details as $detail) {
            if ($payment->purchase_type == DB\paymentX::$PURCHASE_TYPE["entry"]) {
                $new_codes = DataCodes::createNewEntryCodes(
                    $detail->variant_id,
                    $detail->qty,
                    false,
                    false,
                    $payment,
                    $detail);
                $payment->codes->list = array_merge($payment->codes->list, $new_codes->list);
                $processed_payment = true;
            }
            if ($payment->purchase_type == DB\paymentX::$PURCHASE_TYPE["product"]) {
                $new_codes = DataCodes::createNewProductCodes($detail->product_id,$detail->qty,false,false,$payment,$detail);
                $payment->codes->list = array_merge($payment->codes->list, $new_codes->list);
                $processed_payment = true;
            }
        }
        DataAccount::activateNewUser($payment->created_by,false);

        # GENERATE CENTER CREDITS
        if ($payment->purchase_type == DB\paymentX::$PURCHASE_TYPE["ecoin"]) {
            $coinHeader = DataCoin::addFromPurchase($payment);
            $owner = DataUser::get($payment->created_by);
//            $message = "Hello $owner->firstname, your $payment->amount Center Credists has been added to your account";
//            notification_center::notifyViaSMSOrEmail($owner->contact, $owner->email, $message, "Center Credits Purchase");
            $processed_payment = true;
        }

        # GENERATE INVENTORY ENTRY
        if ($payment->purchase_type == DB\paymentX::$PURCHASE_TYPE["inventory_entry"]) {
            Assert::throw("not yet implemented");
//            $inventory_order = inventory_orderX_get::record($payment->inventory_order_id);
//            if ($inventory_order->status != inventory_orderX::$STATUS_ENUM["status_new"]) {
//                assert::throw2("unable to approve payment, inventory order status is not new");
//            }
//            $inventory_order->time_updated = time();
//            $inventory_order->approved_by = $staff_id;
//            $inventory_order->notes = $payment->approver_notes;
//            $inventory_order->screenshots = $payment->screenshot;
//            $inventory_order->status = inventory_orderX::$STATUS_ENUM["status_paid"];
//            $inventory_order->save();
//            $processed_payment = true;
        }

        if (!$processed_payment) {
            Assert::throw("payment approval not complete. process possible not yet implemented");
        }

//        if ($payment->purchase_type != DB\paymentX::$PURCHASE_TYPE["ecoin"]) {
//            Assert::throw("not yet implemented");
//            delivery_headerX::createStandardDeliveryFromApprovedPayment($payment);
//        }

        # CREDITS SYSTEM
        if(DataTopUpCredits::topUpSystemEnabled()){
            $credit_header = DataTopUpCredits::get();
            DataTopUpCredits::useCredits($payment);
        }

        return $payment;
    }


    #endregion END ACTIONS







    #region CHECKS
    private static function assertValidPurchaseType(string $purchase_type){
        if(!in_array($purchase_type,DB\paymentX::$PURCHASE_TYPE))
            Assert::throw("invalid purchase type");
    }
    private static function assertValidPaymentMode(string $payment_mode){
        if(!in_array($payment_mode,DB\paymentX::$PAYMENT_MODES))
            Assert::throw("invalid payment mode");
    }
    private static function checkNewPayment(DB\payment $payment){
        self::assertValidPaymentMode($payment->payment_mode);
        self::assertValidPurchaseType($payment->purchase_type);
        if($payment->status != "n")
            Assert::throw(error_message:"invalid status",critical_error: true);
    }
    #endregion END CHECKS

    #region UTILITIES
    public static function generatePaymentReference(string|int $time_generated = ""): string{
        if (empty($time_generated)) $time_generated = time();
        $ref = "PR" . TimeHelper::getAsFormat($time_generated, "Ymd") . "-";
        $ref .= strtoupper(Random::getRandomStr(8));
        return $ref;
    }

    static function addLog(string $log, int $line)
    {
        Logger::add(msg:$log,category: "payment",line: $line);
    }
    #endregion END UTILITES
}