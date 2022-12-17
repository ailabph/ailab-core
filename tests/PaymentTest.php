<?php

use PHPUnit\Framework\TestCase;
use Ailabph\AilabCore;
use App\DBClassGenerator\DB;

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
    }

    protected function tearDown(): void
    {
        AilabCore\Connection::rollback();
    }

    public function createPayment(): DB\paymentX{
        $admin = AilabCore\DataUser::create([]);
        $admin->username = "admin";
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
}