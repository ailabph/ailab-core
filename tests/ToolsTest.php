<?php

use Ailabph\AilabCore;
use PHPUnit\Framework\TestCase;

class ToolsTest extends TestCase
{
    public function testRootPath(){
        $root_path = AilabCore\Tools::getRootPath();
        $this->assertFalse($root_path,"Tools::getRootPath()");
    }
}