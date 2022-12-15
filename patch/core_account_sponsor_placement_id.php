<?php
use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$allAccounts = new DB\accountList(" WHERE 1 ",[]);
foreach ($allAccounts as $account){
    if(!empty($account->sponsor_account_id)){
        $sponsor = AilabCore\DataAccount::get($account->sponsor_account_id);
        if($account->sponsor_id != $sponsor->id){
            $account->sponsor_id = $sponsor->id;
            $account->save();
        }
    }
    if(!empty($account->placement_account_id)){
        $placement = AilabCore\DataAccount::get($account->placement_account_id);
        if($account->placement_id != $placement->id){
            $account->placement_id = $placement->id;
            $account->save();
        }
    }
}