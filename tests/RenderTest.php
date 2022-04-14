<?php
use Ailabph\AilabCore;
use PHPUnit\Framework\TestCase;

class RenderTest extends TestCase
{
    public function testCreateObject(){
        $render = new AilabCore\Render();
        $this->assertIsObject($render,"Render Object");
    }
}