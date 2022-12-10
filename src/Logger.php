<?php

namespace Ailabph\AilabCore;

use Exception;

class Logger
{
    /**
     * @throws Exception
     */
    public static function add(string|array|object $msg, string $category, int $line): void{
        Assert::isNotEmpty($category,"log category");
        $call_stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3);
        if(empty($call_stack)) Assert::throw("unable to log, call stack empty");
        $call_stack = $call_stack[count($call_stack)-1];

        if(!is_string($msg)){
            $msg = PHP_EOL.print_r($msg,true);
        }

        $file_source = str_replace(Config::getBaseDirectory(),"",$call_stack["file"]);
        $method = $call_stack["function"];

        $build_log = "[$file_source:$line] $method -> $msg";

        Tools::log(message:$build_log,category: $category);
    }
}