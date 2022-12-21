<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class Session
{
    public static ?DB\userX $current_user = null;

    public static function sync(){
        if(isset($GLOBALS["user"]) && $GLOBALS["user"] instanceof DB\user && is_null(self::$current_user)){
            self::$current_user = $GLOBALS["user"];
        }
        else if(!is_null(self::$current_user)){
            $GLOBALS["user"] = self::$current_user;
        }
    }

    public static function getCurrentUser(bool $throw = true): DB\userX|false{
        self::sync();
        if($throw) self::assertIsLoggedIn();
        return self::$current_user ?? false;
    }

    public static function assertIsLoggedIn(){
        self::sync();
        if(is_null(self::$current_user)) Assert::throw("user is not logged in");
    }
}