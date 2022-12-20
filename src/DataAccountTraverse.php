<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB\account;

class DataAccountTraverse implements Loggable
{

    /**
     * hooks:
     * - hook_sponsor_action [$current_account, $traverseData]
     * @param DataAccountTraverseInfo $traverseData
     * @param string $hook_sponsor_action
     */
    public static function traverseSponsorUplines(
        DataAccountTraverseInfo &$traverseData,
        string                  $hook_sponsor_action = "",
    ){
        $current_account = $traverseData->source_account;
        self::addLog(
            "running sponsor traverse. current downline:".$current_account->account_code.
            " sponsor_level:".$current_account->sponsor_level
            ,__LINE__);
        while($current_account = DataAccount::getSponsorUpline($current_account)){
            self::addLog(Tools::LINE_SEPARATOR,__LINE__);
            self::addLog(
                "retrieved sponsor upline:".$current_account->account_code.
                " sponsor_level:".$current_account->sponsor_level,__LINE__);
            $traverseData->current_level = $current_account->sponsor_level;
            $traverseData->traverse_count++;
            if(!empty($hook_sponsor_action)){
                if(Assert::isCallable(method:$hook_sponsor_action,throw:true)){
                    self::addLog("running hook function:$hook_sponsor_action",__LINE__);
                    call_user_func_array($hook_sponsor_action,["current_account"=>&$current_account,"traverseData"=>&$traverseData]);
                }
            }
        }
    }

    /**
     * hooks:
     * - hook_binary_action [$current_account, $traverseData]
     * @param DataAccountTraverseInfo $traverseData
     * @param string $hook_binary_action
     */
    public static function traverseBinaryUplines(
        DataAccountTraverseInfo $traverseData,
        string $hook_binary_action = "",
    ){
        $current_account = $traverseData->source_account;
        self::addLog(
            "running binary traverse. current downline:".$current_account->account_code.
            " binary_level:".$current_account->level
            ,__LINE__);
        while($current_account = DataAccount::getBinaryUpline($current_account)){
            self::addLog(
                "retrieved placement upline:".$current_account->account_code.
                " sponsor_level:".$current_account->sponsor_level,__LINE__);
            $traverseData->current_level = $current_account->sponsor_level;
            $traverseData->traverse_count++;
            if(Assert::isCallable(method:$hook_binary_action,throw:false)){
                self::addLog("running hook function:$hook_binary_action",__LINE__);
                call_user_func_array($hook_binary_action,["current_account"=>&$current_account,"traverseData"=>&$traverseData]);
            }
        }
    }

    static function addLog(string $log, int $line)
    {
        Logger::add(msg:$log,category:"complan",line: $line);
    }
}