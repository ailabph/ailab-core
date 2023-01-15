<?php

use PHPUnit\Framework\TestCase;
use Ailabph\AilabCore;
class AccountTest extends TestCase
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

    public function testAccountCdBalance(){
        $user = AilabCore\DataUser::create([]);
        $user->usergroup = "admin";
        $user->username = AilabCore\Random::getRandomStr();
        $user->password = "password123";
        $user->save();
        $variant = CodesTest::createVariant();
        $code = AilabCore\DataCodes::createNewEntryCode($variant,$user);
        $code->special_type = "cd";
        $code->save();
        $account = AilabCore\DataAccount::createAccount($user,$code);
        self::assertEquals("cd",$account->special_type);
        $cd_balance = AilabCore\DataAccount::getCdBalance($account);
        self::assertEquals($variant->price,$cd_balance);
    }
}