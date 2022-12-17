<?php

use PHPUnit\Framework\TestCase;
use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

class CodesTest extends TestCase
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
    }

    public function createVariant(): DB\package_variantX
    {
        $package = new DB\package_header();
        $package->created_by = 0;
        $package->time_created = 0;
        $package->package_tag = "package_1";
        $package->price = 100;
        $package->package_name = "package one";
        $package->status = "o";
        $package->save();
        $variant = new DB\package_variantX();
        $variant->package_id = $package->id;
        $variant->package_name = "variant one";
        $variant->package_tag = "variant_1";
        $variant->price = 100;
        $variant->time_added = 0;
        $variant->save();
        return $variant;
    }

    public function createProduct(): DB\products{
        $prod = new DB\products();
        $prod->unit_price = 100;
        $prod->price = 1000;
        $prod->status = "o";
        $prod->time_created = time();
        $prod->created_by = 0;
        $prod->product_name = "product name";
        $prod->product_tag = "product_tag";
        $prod->save();
        return $prod;
    }

    public function createAdmin():DB\userX{
        $user = AilabCore\DataUser::create([]);
        $user->username = "admin";
        $user->usergroup = "admin";
        $user->password = "1234";
        $user->save();
        return $user;
    }

    public function testCreateEntryCodes(){
        $variant = $this->createVariant();
        $admin = $this->createAdmin();
        $payment = AilabCore\DataPayment::create("e","cash",100,$admin->id);
        $payment->approved_by = $admin->id;
        $payment->save();
        $codes = AilabCore\DataCodes::createNewEntryCodes($variant,2,false,false,$payment,$admin);
        self::assertCount(2,$codes);
    }

    public function testCreateProductCodes(){
        $prod = $this->createProduct();
        $admin = $this->createAdmin();
        $payment = AilabCore\DataPayment::create("p","cash",0,$admin->id);
        $payment = AilabCore\DataPayment::createProductPayment($payment,[
            (object)["prodid"=>$prod->id,"qty"=>2]
        ]);
        $payment->approved_by = $admin->id;
        $payment->save();
        foreach ($payment->details as $detail){
            $codes = AilabCore\DataCodes::createNewProductCodes($prod,2,$payment,$detail,$admin);
            self::assertCount(2,$codes);
        }
    }
}