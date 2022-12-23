<?php
use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$allAccounts = new DB\accountList(" WHERE 1 ",[]);
foreach ($allAccounts as $account){
    $account->propertyExists("sponsor_id");
    if(!empty($account->sponsor_account_id)){
        $sponsor = AilabCore\DataAccount::get($account->sponsor_account_id);
        if($account->sponsor_id != $sponsor->id){
            $account->sponsor_id = $sponsor->id;
        }
    }
    else{
        $account->sponsor_id = 0;
    }
    $account->propertyExists("placement_id");
    if(!empty($account->placement_account_id)){
        $placement = AilabCore\DataAccount::get($account->placement_account_id);
        if($account->placement_id != $placement->id){
            $account->placement_id = $placement->id;
        }
    }
    else{
        $account->placement_id = 0;
    }
    $account->save();
}