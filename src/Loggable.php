<?php

namespace Ailabph\AilabCore;

interface Loggable
{
    static function addLog(string $log, int $line);
}