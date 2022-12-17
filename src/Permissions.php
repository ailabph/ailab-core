<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class Permissions
{
    public static function section(string $section, null|int|string|DB\user $user = null, bool $throw = false): bool{
        if(is_int($user) && $user == 0) return true;

        $isAllowed = false;

        if (!isset($user)) {
            $user = tools::getCurrentUser()->id;
        }
        $user = $user ?? Session::getCurrentUser(true);
        $user = DataUser::get($user);

        if ($user->usergroup == "admin") return true;

        $section = new DB\permission(["section" => $section]);
        if ($section->isNew()) {
            $section->section = $section;
            $section->status = "o";
            $section->usergroups = "";
            $section->save();
        }

        $authorized = explode(",", $section->usergroups);
        if (in_array($user->usergroup, $authorized)) {
            $isAllowed = true;
        }

        if (!$isAllowed && $throw) {
            Assert::throw("not authorized");
        }

        return $isAllowed;
    }
}