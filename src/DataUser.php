<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataUser
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $user = Tools::appClassExist("user");
        $userX = Tools::appClassExist("userX");
        Tools::checkPropertiesExistInClass($userX,["fullname"]);

        self::$initiated = true;
    }

    public static function get(DB\user|string|int $user): DB\userX{
        self::init();
        $to_return = new DB\userX();
        $get_method = "";

        if($user instanceof DB\userX){
            $get_method = ", via passed userX object";
            $to_return = $user;
        }
        else if($user instanceof DB\user){
            $get_method = ", via conversion to userX object, id:$user->id";
            $to_return = self::get($user->id);
        }

        if($to_return->isNew() && is_numeric($user)){
            $get_method = ", via user id:$user";
            $to_return = new DB\userX(["id"=>$user]);
        }

        if($to_return->isNew() && is_string($user)){
            $get_method = ", via username:$user";
            $to_return = new DB\userX(["username"=>$user]);
        }

        if($to_return->isNew()){
            Assert::throw("user record not retrieved from database".$get_method);
        }
        return $to_return;
    }
}