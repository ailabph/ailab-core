<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB\account;
use App\DBClassGenerator\DB\codes;

class DataAccountTraverseInfo
{
    public string $binary_position = "";
    public int $current_level = 0;
    public int $traverse_count = 0;
    public float|string $bonus_given = 0;
    public int $bonus_given_count = 0;
    public ?codes $code_source = null;
    public ?account $source_account = null;
    public int $pv_to_distribute = 0;
    public float|string $bonus_to_give = 0;
}