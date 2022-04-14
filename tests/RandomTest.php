<?php

use PHPUnit\Framework\TestCase;
use Ailabph\AilabCore;

class RandomTest extends TestCase
{
    public function testGeneratorObject(){
        $generator = AilabCore\Random::getGenerator();
        self::assertInstanceOf("RandomLib\Generator",$generator);
    }

    /**
     * @throws Exception
     */
    public function testRandomInt(){
        $num = AilabCore\Random::getRandomInt(0,10);
        self::assertIsInt($num,"Random Int");
    }

    /**
     * @throws Exception
     */
    public function testCharSetInvalidOptionThrowError(){
        self::expectExceptionMessage(AilabCore\Random::INVALID_OPTION_MSG);
        AilabCore\Random::getCharSet("abc");
    }

    public function dataTestCharSetOptions(): array{
        $data = [];
        $data["alpha_all"] = ["alpha_all", "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890"];
        $data["alpha_high"] = ["alpha_high", "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"];
        $data["alpha_low"] = ["alpha_low", "abcdefghijklmnopqrstuvwxyz1234567890"];
        $data["numeric"] = ["numeric", "1234567890"];
        $data["numeric_safe"] = ["numeric_safe", "2345679"];
        $data["alpha_safe_all"] = ["alpha_safe_all", "ACDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz2345679"];
        $data["alpha_safe_high"] = ["alpha_safe_high", "ACDEFGHJKLMNPQRSTUVWXYZ2345679"];
        $data["alpha_safe_low"] = ["alpha_safe_low", "abcdefghjkmnpqrstuvwxyz2345679"];
        $data["letters_low"] = ["letters_low", "abcdefghijklmnopqrstuvwxyz"];
        return $data;
    }

    /**
     * @dataProvider dataTestCharSetOptions
     * @param string $option
     * @param string $result
     * @throws Exception
     */
    public function testCharSetOptions(string $option, string $result){
        $chars = AilabCore\Random::getCharSet($option);
        self::assertEquals($result,$chars);
    }

    /**
     * @throws Exception
     */
    public function testLength(){
        $default_length = AilabCore\Random::getRandomStr();
        self::assertEquals(8,strlen($default_length));
        $length_24 = AilabCore\Random::getRandomStr(24);
        self::assertEquals(24,strlen($length_24));
    }
}