<?php

use PHPUnit\Framework\TestCase;
use Ailabph\AilabCore\Tools;

class ToolsTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testToolsGetValueFromArrayWithValue(){
        $test_array = ["name"=>"bob"];
        $value = Tools::getValueFromArray("name",$test_array);
        self::assertEquals("bob",$value);
    }

    /**
     * @throws Exception
     */
    public function testToolsGetValueFromArrayReturnNull(){
        $test_array = ["age"=>23];
        $value = Tools::getValueFromArray("name",$test_array);
        self::assertNull($value);
    }

    public function testToolsGetValueFromArrayThrowError(){
        $test_array = ["name"=>"bob"];
        $this->expectException(Exception::class);
        Tools::getValueFromArray("age",$test_array,true);
    }

    // -------

    /**
     * @throws Exception
     */
    public function testImportValuesFromArrayToObject(){
        $test_obj = (object)["first_name"=>"","last_name"=>""];
        $test_array = ["first_name"=>"bob","last_name"=>"joe","age"=>23];
        Tools::importValuesFromArrayToObject($test_array,$test_obj);
        self::assertEquals("bob",$test_obj->first_name);
        self::assertEquals("joe",$test_obj->last_name);
        self::assertFalse(isset($test_obj->age),"isset test_obj->age");
    }

    public function testImportValuesFromArrayToObjectStrictThrowError(){
        $test_obj = (object)["first_name"=>""];
        $test_array = ["last_name"=>"joe"];
        self::expectException(Exception::class);
        Tools::importValuesFromArrayToObject($test_array,$test_obj,true);
    }
}