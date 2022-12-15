<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;
use Exception;

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

    public static array $hooks = [
        "beforeSave" => "",
        "afterSave" => "",
        "processDataBeforeCreate" => "",
        "processUserAfterCreate" => "",
    ];

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

    /**
     * Hooks:
     * - processDataBeforeCreate(array)
     * - processUserAfterCreate(userX)
     *
     * @param array $data
     * @param bool $saveRecord
     * @return DB\userX
     * @throws Exception
     */
    public static function create(array $data, bool $saveRecord = false): DB\userX{
        self::processDataBeforeCreate($data);
        $user = new DB\userX();
        $user->qr_hash = Random::getRandomStr(length: 8);
        $user->loadValues(data:$data, strict:true);
        self::processUserAfterCreate($user);
        if($saveRecord) $user = self::save($user);
        return $user;
    }

    /**
     * Hooks:
     * - beforeSave(userX)
     * - afterSave(userX)
     *
     * @param DB\userX $user
     * @return DB\userX
     * @throws Exception
     */
    public static function save(DB\userX $user): DB\userX{
        if($user->isNew()){
            $user->password = password_hash($user->password, PASSWORD_DEFAULT);
            $user->time_created = TimeHelper::getCurrentTime()->getTimestamp();
        }
        $user->time_last_update = TimeHelper::getCurrentTime()->getTimestamp();
        self::beforeSave($user);
        $user->save();
        self::afterSave($user);
        return $user;
    }

    #region hooks

    private static function processDataBeforeCreate(array &$data){
        $hook_name = "processDataBeforeCreate";
        if(empty(self::$hooks[$hook_name])) return;
        Assert::isCallable(self::$hooks[$hook_name]);
        call_user_func_array(self::$hooks[$hook_name],["data"=>&$data]);
    }

    private static function beforeSave(DB\userX &$user){
        if(empty(self::$hooks["beforeSave"])) return;
        Assert::isCallable(self::$hooks["beforeSave"]);
        call_user_func_array(self::$hooks["beforeSave"],["user"=>&$user]);
    }

    private static function afterSave(DB\userX &$user){
        if(empty(self::$hooks["afterSave"])) return;
        Assert::isCallable(self::$hooks["afterSave"]);
        call_user_func_array(self::$hooks["afterSave"],["user"=>&$user]);
    }

    private static function processUserAfterCreate(DB\userX &$user)
    {
        $hook_name = "processUserAfterCreate";
        if(empty(self::$hooks[$hook_name])) return;
        Assert::isCallable(self::$hooks[$hook_name]);
        call_user_func_array(self::$hooks[$hook_name],["user"=>&$user]);
    }

    #endregion

}