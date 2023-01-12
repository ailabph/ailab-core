<?php

use PHPUnit\Framework\TestCase;
use Ailabph\AilabCore;
use App\DBClassGenerator\DB;
require_once("TestUtilities.php");

class PaymentTest extends TestCase
{
    static function setUpBeforeClass(): void
    {
        AilabCore\Tools::emptyLogs();
        AilabCore\Config::overrideEnv(AilabCore\Config::ENV["test"]);
    }

    protected function setUp(): void
    {
        AilabCore\Connection::startTransaction();
        $this->testUtility = new TestUtilities();
    }

    protected function tearDown(): void
    {
        AilabCore\Connection::rollback();
    }

    protected TestUtilities $testUtility;

    public function createPayment(): DB\paymentX{
        $admin = AilabCore\DataUser::create([]);
        $admin->username = "admin_".AilabCore\Random::getRandomStr();
        $admin->password = "1234";
        $admin->usergroup = "admin";
        $admin->save();
        $payment = AilabCore\DataPayment::create(
            purchase_type: "e"
            , payment_mode: "cash"
            , amount: 1000
            , created_by: "admin"
            , time_created: time()
            , data: []
        );
        return $payment;
    }
    public function testCreatePayment(){
        $payment = $this->createPayment();
        self::assertNotEmpty($payment->payment_referrence);
    }

    public function createEntryPayment():DB\paymentX{
        AilabCore\DataTopUpCredits::$OVERRIDE_TOP_UP_ENABLE = false;
        $payment = $this->createPayment();
        $variant = $this->testUtility->getFirstPackageVariant();
        $payment = AilabCore\DataPayment::createEntryPayment(
            $payment,
            [(object)[
                "package_id" => $variant->package_id,
                "variant_id" => $variant->id,
                "qty" => 1
            ]]);
        $admin = $this->testUtility->createAdmin();
        AilabCore\Session::$current_user = $admin;
        return $payment;
    }
    public function testApproveEntryPayment(){
        $payment = $this->createEntryPayment();
        $payment->payment_mode = "cash";
        $payment->save();
        AilabCore\DataPayment::approvePayment(payment:$payment);

        # check paid codes
        $paid_codes = new DB\codesList(" WHERE payment_ref=:ref ",[":ref"=>$payment->payment_referrence]);
        self::assertGreaterThan(0,$paid_codes->count(),"generated paid codes > 0");
        while($code = $paid_codes->fetch()){
            self::assertEmpty($code->special_type,"paid code special type must be empty or null");
        }
    }
    public function testApproveEntryCdPayment(){
        $payment = $this->createEntryPayment();
        $payment->payment_mode = "cd";
        $payment->save();
        AilabCore\DataPayment::approvePayment($payment);

        # check cd codes
        $cd_codes = new DB\codesList(" WHERE payment_ref=:ref ",[":ref"=>$payment->payment_referrence]);
        self::assertGreaterThan(0,$cd_codes->count(),"generated cd codes > 0");
        while($code = $cd_codes->fetch()){
            self::assertEquals("cd",$code->special_type,"cd code special_type");
        }
    }
    public function testApproveEntryFsPayment(){
        $payment = $this->createEntryPayment();
        $payment->payment_mode = "fs";
        $payment->save();
        AilabCore\DataPayment::approvePayment($payment);

        # check cd codes
        $cd_codes = new DB\codesList(" WHERE payment_ref=:ref ",[":ref"=>$payment->payment_referrence]);
        self::assertGreaterThan(0,$cd_codes->count(),"generated fs codes > 0");
        while($code = $cd_codes->fetch()){
            self::assertEquals("fs",$code->special_type,"fs code special_type");
        }
    }
}