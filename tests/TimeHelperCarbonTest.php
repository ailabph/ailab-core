<?php
use Ailabph\AilabCore;
use Ailabph\AilabCore\TimeHelper;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs;

class TimeHelperCarbonTest extends TestCase
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

    public function testGetCurrentTime()
    {
        $now_timestamp = time();
        $now = TimeHelper::getCurrentTime();
        self::assertInstanceOf(Carbon::class, $now, "instance of Carbon");
        self::assertEquals($now_timestamp, $now->getTimestamp(), "current timestamp");
    }

    public function testInvalidDate()
    {
        self::expectExceptionMessage("not a valid date");
        $carbon = TimeHelper::getTimeAsCarbon("123");
    }

    public function testParseCarbonUsingTimestamp()
    {
        $carbon = TimeHelper::getTimeAsCarbon(time());
        self::assertInstanceOf(Carbon::class, $carbon, "instance of carbon");
    }

    public function testStartOfDay()
    {
        $start_date = "2022-01-01 00:00:00";
        $start = TimeHelper::getStartOfDay("2022-01-01 13:11:34");
        self::assertInstanceOf(Carbon::class, $start);
        self::assertEquals($start_date, $start->format(CarbonInterface::DEFAULT_TO_STRING_FORMAT));
    }

    public function testEndOfDay()
    {
        $end_date = "2021-03-04 23:59:59";
        $end = TimeHelper::getEndOfDay("2021-03-04 03:52:12");
        self::assertEquals($end_date, $end->format(CarbonInterface::DEFAULT_TO_STRING_FORMAT));
    }
}