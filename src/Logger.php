<?php

namespace Ailabph\AilabCore;

use Exception;

class Logger
{
    /**
     * @throws Exception
     */
    public static function add(string|array|object $msg, string $category, int $line, bool $always_write = false): void{
        Assert::isNotEmpty($category,"log category");
        $call_stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3);
        if(empty($call_stack)) Assert::throw("unable to log, call stack empty");
        $call_stack = $call_stack[count($call_stack)-1];

        if(!is_string($msg)){
            $msg = PHP_EOL.print_r($msg,true);
        }

        $file_source = str_replace(Config::getBaseDirectory(),"",$call_stack["file"]);
        $method = $call_stack["function"];

        $file_source_parts = explode("/",$file_source);
        $file_name = $file_source_parts[count($file_source_parts)-1];

        $build_log = "[$file_name:$line|$method()] ".$msg;

        Tools::log(message:$build_log,category: $category,force_write: $always_write);
    }
}