<?php

namespace Ailabph\AilabCore;

use App\DBClassGenerator\DB;

class DataImageLog
{
    public static function updateImageRefId(string|null $images, int $ref_id) :void
    {
        if(!isset($images)) return;
        $image_codes = explode(",", $images);
        foreach ($image_codes as $image_code){
            $image_log = new DB\image_log(["image_code" => $image_code]);
            $image_log->ref_id = $ref_id;
            $image_log->save();
        }
    }
}