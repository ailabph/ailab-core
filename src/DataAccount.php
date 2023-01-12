<?php

namespace Ailabph\AilabCore;
use App\DBClassGenerator\DB;
use ReflectionClass;
use ReflectionProperty;

class DataAccount
{
    private static bool $initiated = false;
    private static function init(){
        if(self::$initiated) return;
        $account = Tools::appClassExist("account");
        $accountX = Tools::appClassExist("accountX");
        self::$initiated = true;
    }

    const POSITION = [
        "LEFT" => "l",
        "RIGHT" => "r",
    ];

    #region GETTERS

    public static function get(DB\account|string|int $account, bool $baseOnly = false): DB\accountX|DB\account{
        self::init();
        return DataGeneric::get("account","accountX",$account,"id","account_code",$baseOnly);
    }

    public static function getSponsorUpline(DB\account|string|int $account): DB\accountX|false{
        $account = self::get($account);
        if(!empty($account->sponsor_account_id) && !($account->sponsor_id > 0)){
            Assert::throw(
                "account:".$account->account_code
                ." has sponsor code info:".$account->sponsor_account_id
                ." but has not sponsor_id");
        }
        if(empty($account->sponsor_account_id)) return false;
        $sponsor = self::get($account->sponsor_id);
        if($sponsor->id != $account->sponsor_id) Assert::throw("sponsor id do not match");
        return $sponsor;
    }

    public static function getBinaryUpline(DB\account|string|int $account): DB\accountX|false{
        $account = self::get($account);
        if(!empty($account->placement_account_id) && !($account->placement_id > 0)){
            Assert::throw(
                "account:".$account->account_code
                ." has placement code info:".$account->placement_account_id
                ." but has not placement_id");
        }
        if(empty($account->placement_id)) return false;
        return self::get($account->placement_id);
    }

    public static function getTopAccount(DB\user|string|int $owner): DB\account|false{
        $owner = DataUser::get($owner);
        $topAccount = new DB\accountList(
            " WHERE user_id=:user_id ",
            [":user_id"=>$owner->id]," ORDER BY level ASC LIMIT 1 "
        );
        if($topAccount->count() == 0){
            Assert::throw("unable to retrieve top account, user $owner->username has no account");
        }
        return $topAccount->fetch();
    }

    public static function findOpenExtremeSide(DB\account $upline, string $side): DB\account{
        if(!in_array($side,DataAccount::POSITION)) Assert::throw("account binary position is not valid");
        $pos_word = $side == DataAccount::POSITION["RIGHT"] ? "right" : "left";

        $open_account = new DB\account();

        $downlineWhere = " WHERE dna LIKE :upline_dna AND (down_$pos_word=:empty OR down_$pos_word IS NULL) ";
        $downlineParam = [
            ":upline_dna" => $upline->dna."%",
            ":empty" => "",
        ];
        $downlines = new DB\accountList($downlineWhere,$downlineParam," ORDER BY level DESC ","accountX");
        $opposite_side = $side == DataAccount::POSITION["RIGHT"] ? DataAccount::POSITION["LEFT"] : DataAccount::POSITION["RIGHT"];
        while($downline = $downlines->fetch()){
            if($downline->id == $upline->id) return $downline;
            $isolate_dna = str_replace($upline->dna,"",$downline->dna);
            if(str_contains($isolate_dna, $opposite_side)) continue;
            $open_account = $downline;
        }
        if($open_account->isNew()) Assert::throw("Something went wrong, unable to find account on extreme $pos_word");
        return $open_account;
    }

    #endregion END OF GETTERS

    #region CHECKS
    public static function checkIntegrityAndSave(DB\account &$account){
        if($account->sponsor_id < 0 && empty($account->sponsor_account_id)){
            $account->sponsor_id = 0;
        }
        if($account->placement_id < 0 && empty($account->placement_account_id)){
            $account->placement_id = 0;
        }
        $account->save();
    }
    #endregion

    #region UTILITIES
    public static function setPlacement(DB\account $upline, DB\codes $downline_code, string $position): DB\account{
        Assert::inTransaction();

        // check if downline_code already on other uplines
        $other_upline = new DB\accountList(" WHERE down_left=:code OR down_right=:code ",[":code"=>$downline_code->code]);
        if($other_upline->count() > 0) Assert::throw("$downline_code->code upline information is already set");

        $position_word = strtolower(array_search($position,DataAccount::POSITION));
        $position_property = "down_".$position_word;
        if(!property_exists($upline,$position_property)) Assert::throw("property:$position_property does not exist in account class");
        if(!empty($upline->{$position_property})) Assert::throw("upline placement:$upline->account_code $position_word downline position is not available. Currently occupied by account:".$upline->{$position_property});
        $upline->{$position_property} = $downline_code->code;
        $upline->save();
        return $upline;
    }

    public static function setDnaInfo(DB\account $target_account): DB\account{
        Assert::inTransaction();
        $target_account->dna = $target_account->position.$target_account->id;
        if(!empty($target_account->placement_account_id)){
            $placement = DataAccount::get($target_account->placement_account_id);
            if(empty($placement->dna)) Assert::throw("placement upline:$placement->account_code has an empty dna");
            $target_account->dna = $placement->dna."_".$target_account->position.$target_account->id;
        }
        $target_account->sponsor_dna = $target_account->id;
        if(!empty($target_account->sponsor_account_id)){
            $sponsor = DataAccount::get($target_account->sponsor_account_id);
            if(empty($sponsor->sponsor_dna)) Assert::throw("sponsor upline:$sponsor->account_code has an empty sponsor dna");
            $target_account->sponsor_dna = $sponsor->sponsor_dna."_".$target_account->id;
        }
        $target_account->save();
        return $target_account;
    }
    #endregion END OF UTILITIES

    #region PROCESS

    public static function encodeBundleCode(DB\user|string|int $user, DB\codes|string|int $code, DB\account|string|int $placement, DB\account|string|int $sponsor): DB\codesList
    {
        Assert::inTransaction();

        // check data
        $user = DataUser::get($user);
        $code = DataCodes::get($code);
        if($code->code_type !== DataCodes::TYPE["ENTRY"]) Assert::throw("unable to encode bundle, code is not of entry type");
        $placement = DataAccount::get($placement);
        $sponsor = DataAccount::get($sponsor);
        if(!empty($placement->down_left) || !empty($placement->down_right)) Assert::throw("Placement account must have no downlines");
        $variant = DataPackageVariant::get($code->variant_id);
        if(empty($variant->bundle)) Assert::throw("variant:$variant->package_tag is not a bundle type");

        // generate codes from bundle
        $variant_ids = explode(",",$variant->bundle);
        $bundle_codes = new DB\codesList(" WHERE 0 ",[]);
        foreach($variant_ids as $variant_id){
            $bundle_composition_variant = DataPackageVariant::get($variant_id);
            if(!empty($bundle_composition_variant->bundle)) Assert::throw("composition of the bundle consists of variants that is also a bundle");
            $args = null;
            if(!empty($code->payment_ref)){
                $args = DataPayment::get($code->payment_ref);
            }
            if(!empty($code->order_id)){
                $args = DataOrderHeader::get($code->order_id);
            }
            $bundle_code = DataCodes::createNewEntryCode($bundle_composition_variant,$args);
            $bundle_codes->list[] = $bundle_code;
        }

        // encode generated codes
        $newAccounts = [];
        $accountWalker = 0;
        $accountCreated = 0;
        while($bundle_code = $bundle_codes->fetch()){
            $side = empty($placement->down_left) ? "l" : "r";
            $accountCreated++;
            $newAccount = DataAccount::createAccount(user:$user,code:$bundle_code,placement:$placement,sponsor:$sponsor,side:$side,override_placement: false,autoGenerated:true);
            $placement->refresh();
            $newAccounts["a_$accountCreated"] = $newAccount;
            if(!empty($placement->down_left) && !empty($placement->down_right)){
                $accountWalker++;
                $placement = $newAccounts["a_$accountWalker"];
            }
        }
        return $bundle_codes;
    }

    public static function createAccount(
        DB\user|string|int $user,
        DB\codes|string|int $code,
        DB\account|string|int|null $placement,
        DB\account|string|int|null $sponsor,
        string $side,
        bool $override_placement = false,
        $custom_time = null,
        bool $autoGenerated = false,
        string $specialType = "",
        bool $disable_head_limit = false
    ): DB\account
    {
        Assert::inTransaction();

        // prepare data
        $user = DataUser::get($user);
        $code = DataCodes::get($code);
        $code = DataCodes::setEntryCodeAsUsed($code,$user);
        $placement = is_null($placement) ? new DB\account() : DataAccount::get($placement);
        $sponsor = is_null($sponsor) ? new DB\account() : DataAccount::get($sponsor);
        if(!in_array($side,DataAccount::POSITION)) Assert::throw("invalid account binary position:$side");
        if($placement->isNew() || $sponsor->isNew()){
            if($user->usergroup != "admin") Assert::throw("only admin can encode an account without an upline");
        }
        if(!$override_placement && $placement->recordExists()){
            $placement = DataAccount::setPlacement($placement,$code,$side);
        }

        $variant = DataPackageVariant::get($code->variant_id);
        $owned_accounts = new DB\accountList(" WHERE user_id=:user_id ",[":user_id"=>$user->id]);
        if(!$disable_head_limit && !$autoGenerated){
            $max_head = 0;
            $config_complan_class = "App\DBClassGenerator\DB\config_complan";
            if(class_exists($config_complan_class)){
                $property_name = "MAX_HEAD";
                $reflect = new ReflectionClass($config_complan_class);
                if($reflect->hasProperty($property_name)){
                    $property_value = new ReflectionProperty($config_complan_class,$property_name);
                    $max_head = $property_value->getValue();
                }
            }
            if($max_head > 0 && $user->usergroup != "admin" && $owned_accounts->count() >= $max_head){
                Assert::throw("Maximum heads you can encode is only $max_head found ".$owned_accounts->count());
            }
        }

        $account = new DB\account();
        $account->is_top = $owned_accounts->count() == 0 ? "y" : "n";
        $account->rank = "basic";
        $account->user_id = $user->id;
        $account->account_code = $code->code;
        $account->account_pin = $code->pin;
        $package_header = DataPackage::get($code->package_id);
        $account->account_type = $package_header->package_tag;
        $account->time_created = TimeHelper::getCurrentTime()->getTimestamp();

        // set sponsor info
        $account->sponsor_id = 0;
        $account->sponsor_account_id = "";
        if($sponsor->recordExists()){
            $account->sponsor_account_id = $sponsor->account_code;
            $account->sponsor_id = $sponsor->id;
        }

        // set placement info
        $account->placement_id = 0;
        $account->placement_account_id = "";
        if($placement->recordExists()){
            $account->placement_account_id = $placement->account_code;
            $account->placement_id = $placement->id;
        }

        $account->position = $side;
        $account->level = empty($placement->account_code) ? 1 : $placement->level + 1;
        $account->sponsor_level = empty($sponsor->account_code) ? 1 : $sponsor->sponsor_level + 1;
        $account->down_left = "";
        $account->down_right = "";
        $account->dna = "";
        $account->sponsor_dna = "";
        $account->point_leftpv = 0;
        $account->point_rightpv = 0;
        $account->total_pair = 0;
        $account->point_unilevel = 0;
        $account->total_binary_in = 0;
        $account->total_binary_out = 0;
        $account->total_binary_balance = 0;
        $account->special_type = empty($code->special_type) ? "" : $code->special_type;
        $account->status = "o";

        if($autoGenerated){
            $account->auto_generated = "y";
        }

        $account->save();
        $account = DataAccount::setDnaInfo($account);

        $code->account_id = $account->id;
        $code->placement_dna = $account->dna;
        $code->sponsor_dna = $account->sponsor_dna;
        $code->is_encode = "y";
        $code->save();

        #region SETUP ADDITIONAL ACCOUNTS
        if($code->variant_id > 0){
            $variant = DataPackageVariant::get($code->variant_id);
            if(!empty($variant->bundle)){
                $codes = DataAccount::encodeBundleCode($user,$code,$account,$account);
            }
        }
        #endregion

        return $account;
    }

    public static function activateNewUser(int $user_id, bool $strict = true): array|string
    {
        Assert::inTransaction();
        $target_user = DataUser::get($user_id);

        // user group must be new to activate
        if ($target_user->usergroup != "new") {
            $error_message = "Cannot activate, user is already a member";
            if ($strict) Assert::throw($error_message);
            return $error_message;
        }

        // must have unused paid entry code
        $codes = DataCodes::getUnusedPaidEntryCodes($target_user);
        if ($codes->count() == 0) {
            $error_message = "Cannot activate, user has no unused paid entry code in code bank";
            if ($strict) Assert::throw($error_message);
            return $error_message;
        }
        $code_to_use = $codes->fetch();

        // referral code must not be empty
        if (empty($target_user->referred_by_code)) {
            Assert::throw("Cannot activate, has no valid referral code. Referral code is empty");
        }

        // referral code must be valid username or account
        $referral_parts = explode("-", $target_user->referred_by_code);
        $position = "";
        if (isset($referral_parts[1])) {
            $position = $referral_parts[1];
        }
        $upline_user = new DB\user(["username" => $referral_parts[0]]);
        $upline_account = new DB\account(["account_code" => $referral_parts[0]]);
        if ($upline_user->isNew() && $upline_account->isNew())
            Assert::throw("Unable to retrieve upline username or account from referral code");

        if (!empty($position)) {
            $position = strtolower($position);
            if (!in_array($position, ["l", "r"])) {
                Assert::throw("Invalid positioning on username referral code");
            }
        } else {
            $position = "l";
        }

        if (!$upline_user->isNew()) {
            $top_account = DataAccount::getTopAccount($upline_user);
            $placement = DataAccount::findOpenExtremeSide($top_account,$position);
            $new_account = DataAccount::createAccount($target_user,$code_to_use,$placement,$top_account,$position);
        } else {
            $placement = DataAccount::findOpenExtremeSide($upline_account,$position);
            $new_account = DataAccount::createAccount($target_user,$code_to_use,$placement,$upline_account,$position);
        }
        if(empty($new_account) || $new_account->isNew()) Assert::throw("new account is not created for new user");

        $target_user->usergroup = "member";
        $target_user->time_last_update = TimeHelper::getCurrentTime()->getTimestamp();
        $target_user->save();

        DataSms::addSmsQueue($target_user->contact,"Congratulations! Your account has been Activated!");

        return [$target_user, $new_account];
    }

    # TODO: for implementation
    public static function encode(DB\user $user, DB\codes $entry_code, $placement, $position, $sponsor): DB\account{
        // hook before encode
        // hook after encode
    }
    # TODO: for implementation
    public static function upgrade(DB\account $account, DB\codes $upgrade_code): account{
        // hook before upgrade
        // hook after upgrade
    }

    #endregion END OF PROCESS
}