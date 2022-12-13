<?php

use Ailabph\AilabCore;
use Ailabph\AilabCore\Tools;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs;

class ToolsTest extends TestCase
{
    #region SETUP MOCK CONFIG
    static private string $original_DIR = "";
    static string $RANDOM_DIR_NAME = "";
    public string $originalScriptDir = "";

    public static function setUpBeforeClass():void
    {
        AilabCore\Config::resetCache();
        AilabCore\Config::resetOverrideEnv();
        self::$original_DIR = AilabCore\Config::$CURRENT_DIR;
    }

    public static function tearDownAfterClass(): void
    {
        AilabCore\Config::$CURRENT_DIR = self::$original_DIR;
    }

    protected function setUp(): void{
        vfs\vfsStream::setup($this->getTestDir());
        vfs\vfsStream::setup($this->getTestDir()."/logs");
        AilabCore\Config::$OVERRIDE_PATH = vfs\vfsStream::url($this->getTestDir()."/");

        $content = ";<?php die();".PHP_EOL;
        $content .= ";/*".PHP_EOL;
        $content .= 'db_host = "db_host" '.PHP_EOL;
        $content .= 'db_name = "test_db_name" '.PHP_EOL;
        $content .= 'db_user = "db_user" '.PHP_EOL;
        $content .= 'db_pass = "db_pass" '.PHP_EOL;
        $content .= ";*/".PHP_EOL;
        file_put_contents(AilabCore\Config::$OVERRIDE_PATH."/config.ini.php",$content);
        $content = ";<?php die();".PHP_EOL;
        $content .= ";/*".PHP_EOL;
        $content .= 'test_prop = "test_value"'.PHP_EOL;
        file_put_contents(AilabCore\Config::$OVERRIDE_PATH."/config_public.ini.php",$content);

        $this->originalScriptDir = AilabCore\Config::$CURRENT_DIR;
    }

    protected function tearDown(): void
    {
        AilabCore\Config::$OVERRIDE_PATH = "";
        AilabCore\Config::$CURRENT_DIR = self::$original_DIR;
    }

    /**
     * @throws Exception
     */
    public function getTestDir(): string{
        if(!empty(static::$RANDOM_DIR_NAME)) return static::$RANDOM_DIR_NAME;
        static::$RANDOM_DIR_NAME = AilabCore\Random::getRandomStr(5,AilabCore\Random::OPTION_ALPHA_LOW);
        return static::$RANDOM_DIR_NAME;
    }
    #endregion

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
        Tools::importValuesToObject($test_array,$test_obj);
        self::assertEquals("bob",$test_obj->first_name);
        self::assertEquals("joe",$test_obj->last_name);
        self::assertFalse(isset($test_obj->age),"isset test_obj->age");
    }

    public function testImportValuesFromArrayToObjectStrictThrowError(){
        $test_obj = (object)["first_name"=>""];
        $test_array = ["last_name"=>"joe"];
        self::expectException(Exception::class);
        Tools::importValuesToObject($test_array,$test_obj,true);
    }
}