<?php

use Ailabph\AilabCore;
use App\DBClassGenerator\DB;
use PHPUnit\Framework\TestCase;

class TestUtilities extends TestCase
{
    public function createAdmin(): DB\userX{
        $admin = AilabCore\DataUser::create([]);
        $admin->username = "admin_".AilabCore\Random::getRandomStr();
        $admin->password = "admin";
        $admin->usergroup = "admin";
        $admin->save();
        return $admin;
    }

    public function getFirstPackageVariant():DB\package_variant{
        $variants = new DB\package_variantList(where:" WHERE 1 ",param:[],order:" ORDER BY id ASC LIMIT 1 ");
        $variant = $variants->fetch();
        if(!$variant) AilabCore\Assert::throw("no variants on db");
        return $variant;
    }
}