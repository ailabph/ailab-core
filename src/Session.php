<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class Session
{
    public static ?DB\userX $current_user = null;

    public static function getCurrentUser(): DB\userX|false{
        if(isset($GLOBALS["user"])){
            self::$current_user = $GLOBALS["user"];
        }
        return self::$current_user ?? false;
    }
}