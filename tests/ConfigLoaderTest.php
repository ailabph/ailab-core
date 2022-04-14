<?php

use PHPUnit\Framework\TestCase;
use Ailabph\AilabCore;
use org\bovigo\vfs;

class ConfigLoaderTest extends TestCase
{
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

    /* --------------------------------------------------------------------------------------------------------- */

    public function testPutFile(){
        file_put_contents(AilabCore\Config::$OVERRIDE_PATH."/hello.txt","hey there");
        $content = file_get_contents(AilabCore\Config::$OVERRIDE_PATH."/hello.txt");
        $this->assertStringContainsString("hey there",$content);
    }

    public function testCorrectTestConfigDir(){
        AilabCore\Config::$OVERRIDE_PATH = "";
        $this->assertStringNotContainsString(
            static::$RANDOM_DIR_NAME,AilabCore\Config::getBaseDirectory(),
            "base dir must not contain str ".static::$RANDOM_DIR_NAME);
    }

    public function testTestEnvConfigDir(){
        $this->assertStringContainsString(
            static::$RANDOM_DIR_NAME, AilabCore\Config::getBaseDirectory(),
            "base dir:".AilabCore\Config::getBaseDirectory()." must contain ".static::$RANDOM_DIR_NAME);
    }

    public function testGetConfigFunction(){
        $config = AilabCore\Config::getConfig();
        $this->assertTrue(
            ($config instanceof AilabCore\Config),
            "getConfig() must return a AilabCore\Config object"
        );
    }

    public function testGetDbCredentials(){
        $config = AilabCore\Config::getConfig();
        $correct_db_name = "test_db_name";
        $this->assertTrue(($config->db_name == $correct_db_name),
            "retrieved db_name(".$config->db_name.") must be equal to $correct_db_name");
    }

    public function testGetPublicConfig(){
        $test_property = AilabCore\Config::getPublicOption("test_prop");
        $this->assertStringContainsString("test_value",$test_property,
            "test_prop value ($test_property) must contain test_value");
    }

    public function testGetPublicNotRequiredConfig(){
        $test_property = AilabCore\Config::getPublicOption("test_prop_not_exist");
        $this->assertEmpty($test_property,
            "test_prop_not_exist must be empty");
    }

    public function testGetPublicRequiredConfig(){
        $this->expectException(Exception::class);
        $test_property = AilabCore\Config::getPublicOption("test_prop_not_exist",true);
    }


    public function testLocalEnv(){
        unset($_SERVER["REMOTE_ADDR"]);
        $this->assertStringContainsString("local",AilabCore\Config::getEnv());
        $this->assertStringNotContainsString("staging",AilabCore\Config::getEnv());
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $this->assertStringContainsString("local",AilabCore\Config::getEnv());
    }

    public function testLiveEnv(){
        $_SERVER["REMOTE_ADDR"] = "255.255.255.255";
        $this->assertStringNotContainsString("local",AilabCore\Config::getEnv());
        $this->assertStringContainsString("live",AilabCore\Config::getEnv());
    }

    public function testStagingEnv(){
        AilabCore\Config::$CURRENT_DIR .= "/staging";
        $this->assertStringContainsString("staging",AilabCore\Config::getEnv());
    }

    public function testLatestEnv(){
        AilabCore\Config::$CURRENT_DIR .= "/latest";
        $this->assertStringContainsString("latest",AilabCore\Config::getEnv());
    }

    public function testOverrideEnv(){
        $content = 'test_env = "env_value_base"'.PHP_EOL;
        $content .= '[local]'.PHP_EOL.'test_env = "env_value_local"'.PHP_EOL;
        $content .= '[latest]'.PHP_EOL.'test_env = "env_value_latest"'.PHP_EOL;
        $content .= '[live]'.PHP_EOL.'test_env = "env_value_live"'.PHP_EOL;
        file_put_contents(AilabCore\Config::$OVERRIDE_PATH."/config_public.ini.php",$content);
        AilabCore\Config::overrideEnv(AilabCore\Config::ENV["local"]);
        $prop = AilabCore\Config::getPublicOption("test_env",false,true);
        $this->assertStringContainsString("env_value_local",$prop);
        AilabCore\Config::overrideEnv(AilabCore\Config::ENV["latest"]);
        $prop = AilabCore\Config::getPublicOption("test_env",false,true);
        $this->assertStringContainsString("env_value_latest",$prop);
        AilabCore\Config::overrideEnv(AilabCore\Config::ENV["live"]);
        $prop = AilabCore\Config::getPublicOption("test_env",false,true);
        $this->assertStringContainsString("env_value_live",$prop);
    }

    /**
     * @throws Exception
     */
    public function testGetBaseDir(){
        # NOTES: This test works only on MacOS and MAMP installed
        AilabCore\Config::$OVERRIDE_PATH = "";
        $expected = "/Applications/MAMP/htdocs/ailab-core";
        self::assertEquals($expected, AilabCore\Config::getBaseDirectory());
    }
}