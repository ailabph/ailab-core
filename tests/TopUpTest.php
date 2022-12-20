<?php

use Ailabph\AilabCore\DataTopUpRequest;
use PHPUnit\Framework\TestCase;
use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

class TopUpTest extends TestCase
{
    static function setUpBeforeClass(): void
    {
        AilabCore\Config::overrideEnv(AilabCore\Config::ENV["test"]);
    }

    protected function setUp(): void
    {
        AilabCore\Connection::startTransaction();
    }

    protected function tearDown(): void
    {
        AilabCore\Connection::rollback();
        AilabCore\DataTopUpCredits::$OVERRIDE_TOP_UP_ENABLE = null;
    }

    #region CHECK PAYMENTS
    public function createEntryPayment(array $data = [], array $details = [], ?int $staff_id = null): DB\paymentX
    {
        $payment = AilabCore\DataPayment::create(purchase_type: "e", payment_mode: "cash", amount: 0, created_by: $staff_id, data: $data);
        return AilabCore\DataPayment::createEntryPayment($payment, $details);
    }

    public function approvePayment(DB\payment $payment, DB\user $staff): DB\paymentX
    {
        return AilabCore\DataPayment::approvePayment(payment: $payment, staff_id: $staff->id);
    }

    public function createAdmin(): DB\userX{
        $admin = AilabCore\DataUser::create([]);
        $admin->username = AilabCore\Random::getRandomStr();
        $admin->usergroup = "admin";
        $admin->password = "1234";
        return AilabCore\DataUser::save($admin);
    }

    public function createCenter(): DB\userX{
        $center = AilabCore\DataUser::create([]);
        $center->username = AilabCore\Random::getRandomStr();
        $center->usergroup = "mobile_stockist";
        $center->password = "1234";
        return AilabCore\DataUser::save($center);
    }

    public function createEntryVariant(): DB\package_variant{
        $package = new DB\package_header();
        $package->time_created = time();
        $package->package_tag = "package_99";
        $package->package_name = "package name";
        $package->price = 1000;
        $package->status = "o";
        $package->created_by = 0;
        $package->save();

        $variant = new DB\package_variant();
        $variant->package_id = $package->id;
        $variant->package_tag = "variant_99";
        $variant->package_name = "variant name";
        $variant->status = "o";
        $variant->price = 1000;
        $variant->save();

        return $variant;
    }

    public function createProduct(): DB\products{
        $product = new DB\products();
        $product->created_by = 0;
        $product->price = 500;
        $product->unit_price = 50;
        $product->dist_price = 250;
        $product->status = "o";
        $product->time_created = time();
        $product->product_tag = "product_99";
        $product->product_name = "product 99";
        $product->save();
        return $product;
    }

    public function testPaymentApproveEntry()
    {
        $admin = $this->createAdmin();
        AilabCore\Session::$current_user = $admin;
        $variant = $this->createEntryVariant();
        $details = [
            (object)[
                "package_id" => $variant->package_id,
                "qty" => 1,
                "variant_id" => $variant->id,
            ],
        ];
        $payment = $this->createEntryPayment(details: $details, staff_id: $admin->id);
        self::assertFalse($payment->isNew(), "payment record exist");
        self::assertEquals($variant->price, $payment->amount, "payment amount");
        AilabCore\DataTopUpCredits::$OVERRIDE_TOP_UP_ENABLE = false;
        $this->approvePayment($payment, $admin);
        self::assertEquals(DB\paymentX::$STATUS["status_approved"], $payment->status, "payment approval status");
        self::assertEquals(1, $payment->codes->count(), "codes count");
    }

    public function createProductPayment(array $data = [], array $details = [], ?int $staff_id = null): DB\paymentX
    {
        $payment = AilabCore\DataPayment::create(purchase_type: "p", payment_mode: "cash", amount: 0, created_by: $staff_id, data: $data);
        return AilabCore\DataPayment::createProductPayment($payment, $details);
    }

    public function testPaymentApproveProducts()
    {
        $admin = $this->createAdmin();
        AilabCore\Session::$current_user = $admin;
        $product = $this->createProduct();
        $products = [
            (object)[
                "prodid" => $product->id,
                "qty" => 3,
            ],
        ];
        $payment = self::createProductPayment(details: $products, staff_id: $admin->id);
        self::assertFalse($payment->isNew(), "payment record exists");
        self::assertEquals(1, $payment->details->count(), "payment details count");
        AilabCore\DataTopUpCredits::$OVERRIDE_TOP_UP_ENABLE = false;
        $payment = $this->approvePayment($payment, $admin);
        self::assertEquals(DB\paymentX::$STATUS["status_approved"], $payment->status, "payment approval status");
        self::assertEquals(3, $payment->codes->count(), "codes count");
    }

    public function createCoinPayment(float|int $amount, DB\user $user): DB\paymentX
    {
        return AilabCore\DataPayment::create(purchase_type: DB\paymentX::$PURCHASE_TYPE["ecoin"], payment_mode: "cash", amount: $amount, created_by: $user->id);
    }

    public function testPaymentApproveCoins()
    {
        $admin = $this->createAdmin();
        $mobile = $this->createCenter();
        $payment = $this->createCoinPayment(12345, $mobile);
        self::assertEquals(DB\paymentX::$PURCHASE_TYPE["ecoin"], $payment->purchase_type, "purchase type");
        self::assertEquals(12345, $payment->amount, "payment amount");
        self::assertEquals(DB\paymentX::$STATUS["status_new"], $payment->status, "payment status");
        self::assertEquals($mobile->id, $payment->created_by, "created_by");
        AilabCore\Session::$current_user = $admin;
        AilabCore\DataTopUpCredits::$OVERRIDE_TOP_UP_ENABLE = false;
        $this->approvePayment($payment, $admin);
        self::assertEquals(DB\paymentX::$STATUS["status_approved"], $payment->status, "approved payment status");
        $coinHeader = AilabCore\DataCoin::get($mobile);
        self::assertEquals(12345, $coinHeader->amount, "center credits");
    }

    public function createInventoryOrderPayment(array $orderData, DB\user $user): DB\paymentX
    {
        # create order
        $inventory_order = DB\inventory_orderX_do::createInventoryOrder(order_type: DB\inventory_orderX::ORDER_TYPES["product"], center_user: $user);
        # add item
        foreach ($orderData as $data) {
            DB\inventory_orderX_do::addEntryQty($inventory_order, $data["variant_id"], $data["qty"]);
        }
        $inventory_order->loadDetails();
        $payment = DB\paymentX_do::create(
            purchase_type: DB\paymentX::$PURCHASE_TYPE["inventory_entry"],
            payment_mode: DB\paymentX::$PAYMENT_MODES["cash"],
            owner: $user
        );
        return DB\paymentX_do::createInventoryEntryPayment($payment, $inventory_order, $user->id);
    }

    public function xtestPaymentApproveInventoryEntry()
    {
        $admin = $this->createAdmin();
        $center = $this->createCenter();
        $orderData = [
            [
                "variant_id" => 3,
                "qty" => 5
            ],
            [
                "variant_id" => 1,
                "qty" => 2
            ],
        ];
        $payment = $this->createInventoryOrderPayment($orderData, $center);
        $payment->loadPaymentDetails();
        self::assertFalse($payment->isNew(), "payment record in db");
        self::assertEquals(DB\paymentX::$PURCHASE_TYPE["inventory_entry"], $payment->purchase_type, "payment purchase type");
        self::assertEquals(65993, $payment->amount, "payment amount");
        self::assertEquals(2, $payment->details->count(), "payment details count");
        $inventory_order = DB\inventory_orderX_get::byPaymentRef($payment->payment_referrence);
        self::assertEquals(DB\inventory_orderX::$STATUS_ENUM["status_new"], $inventory_order->status, "inventory order status");
        self::assertEquals($payment->amount, $inventory_order->total, "payment and inventory order total");
        self::assertEquals(2, $inventory_order->details->count(), "details count");

        $this->approvePayment($payment, $admin);
        $inventory_headers = DB\inventory_headerX::getAvailableVariantEntry($center);
        self::assertEquals(2, $inventory_headers->count(), "inventory count");

    }

    public function xtestPaymentApproveInventoryProduct()
    {
    }
    #endregion

    #region TOP UP REQUEST
    public function testSystemTopUpRequest()
    {
        $company = $this->createAdmin();
        $company->usergroup = "owner";
        $company->save();

        AilabCore\DataTopUpRequest::add([
            "top_up_amount"=>10000,
            "top_up_notes"=>"notes_here",
            "top_up_screenshot"=>"screenshot_here",
        ],added_by:$company->id);

        $check = AilabCore\DataTopUpRequest::list();
        self::assertCount(1,$check->list);

        $request_header = AilabCore\DataTopUpRequest::get();
        self::assertEquals(0,$request_header->total_top_up_approved);
        self::assertEquals(0,$request_header->total_top_up_denied);
        self::assertEquals(0,$request_header->total_credits_in);
        self::assertEquals(0,$request_header->total_credits_used);

        $credit_header = AilabCore\DataTopUpCredits::get();
        self::assertEquals(0,$credit_header->total_credits_balance);
    }

    public function testSystemTopUpRequestApprove()
    {
        $company = $this->createAdmin();
        $company->usergroup = "owner";
        $company->save();
        $detail = DataTopUpRequest::add([
            "top_up_amount"=>10000,
            "top_up_notes"=>"notes_here",
            "top_up_screenshot"=>"screenshot_here",
        ],added_by:$company->id);

        $approver = $this->createAdmin();

        DataTopUpRequest::approve([
            "detail_id"=>$detail->id,
            "process_remarks"=>"remarks here",
        ],$approver->id);

        $check = DataTopUpRequest::detailRecord($detail->id);
        self::assertEquals(DataTopUpRequest::PROCESS_STATUS["approved"],$check->process_status,"process_status");

        $header = DataTopUpRequest::get();
        self::assertEquals(10000,$header->total_top_up_approved,"total_top_up_approved");
        self::assertEquals(200000,$header->total_credits_in,"total_credits_in");

        $credit_header = AilabCore\DataTopUpCredits::get();
        self::assertEquals(200000,$credit_header->total_credits_balance);
    }

    public function testSystemTopUpRequestDeny()
    {
        $company = $this->createAdmin();
        $company->usergroup = "owner";
        $company->save();
        $detail = DataTopUpRequest::add([
            "top_up_amount"=>10000,
            "top_up_notes"=>"notes_here",
            "top_up_screenshot"=>"screenshot_here",
        ],added_by:$company->id);

        $approver = $this->createAdmin();

        DataTopUpRequest::deny([
            "detail_id"=>$detail->id,
            "process_remarks"=>"remarks here",
        ],$approver->id);

        $check = DataTopUpRequest::detailRecord($detail->id);
        self::assertEquals(DataTopUpRequest::PROCESS_STATUS["denied"],$check->process_status,"process_status");

        $header = DataTopUpRequest::get();
        self::assertEquals(0,$header->total_top_up_approved,"total_top_up_approved");
        self::assertEquals(0,$header->total_credits_in,"total_credits_in");

        $credit_header = AilabCore\DataTopUpCredits::get();
        self::assertEquals(0,$credit_header->total_credits_balance);
    }

    public function testSystemTopUpApproveWithNoCredits(){
        $admin = $this->createAdmin();
        $variant = $this->createEntryVariant();
        $details = [
            (object)[
                "package_id" => $variant->package_id,
                "qty" => 1,
                "variant_id" => $variant->id,
            ],
        ];
        $payment = $this->createEntryPayment(details: $details, staff_id: $admin->id);
        self::assertFalse($payment->isNew(), "payment record exist");
        self::assertEquals($variant->price, $payment->amount, "payment amount");
        self::expectExceptionMessage("not enough credit");
        $this->approvePayment($payment, $admin);
    }

    public function testSystemTopUpApproveWithCredits(){
        $admin = $this->createAdmin();
        $variant = $this->createEntryVariant();
        $details = [
            (object)[
                "package_id" => $variant->package_id,
                "qty" => 1,
                "variant_id" => $variant->id,
            ],
        ];

        $company = $this->createAdmin();
        $company->usergroup = "owner";
        $company->save();
        $detail = DataTopUpRequest::add([
            "top_up_amount"=>10000,
            "top_up_notes"=>"notes_here",
            "top_up_screenshot"=>"screenshot_here",
        ],added_by:$company->id);

        $approver = $this->createAdmin();

        DataTopUpRequest::approve([
            "detail_id"=>$detail->id,
            "process_remarks"=>"remarks here",
        ],$approver->id);

        $payment = $this->createEntryPayment(details: $details, staff_id: $admin->id);
        $this->approvePayment($payment, $admin);

        $credit_header = AilabCore\DataTopUpCredits::get();
        self::assertEquals($variant->price,$credit_header->total_credits_out,"total_credits_out");
        self::assertEquals(199000,$credit_header->total_credits_balance,"total_credits_balance");

        $credit_detail = new DB\top_up_credits_detail(["payment_id"=>$payment->id]);
        self::assertFalse($credit_detail->isNew(),"credit detail exist");

        self::assertEquals($variant->price,$credit_detail->credits_out,"credits out");
        self::assertEquals(199000,$credit_detail->running_credit_balance,"running credit balance");

    }
    #endregion

    #region APPROVAL NO BALANCE
    public function xtestSystemTopUpNoBalance()
    {
    }

    public function xtestSystemTopUpNoBalanceApproveEntryError()
    {
    }

    public function xtestSystemTopUpNoBalanceApproveProductError()
    {
    }

    public function xtestSystemTopUpNoBalanceApproveCoinsError()
    {
    }

    public function xtestSystemTopUpNoBalanceApproveInventoryEntryError()
    {
    }

    public function xtestSystemTopUpNoBalanceApproveInventoryProductError()
    {
    }
    #endregion

    #region APPROVAL WITH BALANCE
    public function xtestSystemTopUpWithBalance()
    {
    }

    public function xtestSystemTopUpWithBalanceApproveEntry()
    {
    }

    public function xtestSystemTopUpWithBalanceApproveMoreThanBalanceError()
    {
    }
    #endregion
}