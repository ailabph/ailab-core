<?php

namespace Ailabph\AilabCore;

use Exception;
use RandomLib\Factory;
use RandomLib\Generator;
use SecurityLib\Strength;

class Random
{
    static private Factory $FACTORY;
    static private Strength $STRENGTH;
    static private Generator $GENERATOR;

    const ALPHABET_LOW  = "abcdefghijklmnopqrstuvwxyz";
    const ALPHABET_HIGH = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const NUMERIC = "1234567890";


    const NUMERIC_SAFE = "2345679";
    const ALPHABET_SAFE_LOW  = "abcdefghjkmnpqrstuvwxyz";
    const ALPHABET_SAFE_HIGH = "ACDEFGHJKLMNPQRSTUVWXYZ";

    const DEFAULT_LENGTH = 8;

    const OPTION_ALPHA_ALL = "alpha_all";
    const OPTION_ALPHA_HIGH = "alpha_high";
    const OPTION_ALPHA_LOW = "alpha_low";
    const OPTION_NUMERIC = "numeric";
    const OPTION_NUMERIC_SAFE = "numeric_safe";
    const OPTION_ALPHA_SAFE_ALL = "alpha_safe_all";
    const OPTION_ALPHA_SAFE_HIGH = "alpha_safe_high";
    const OPTION_ALPHA_SAFE_LOW = "alpha_safe_low";
    const OPTION_LETTERS_LOW = "letters_low";

    static public array $OPTION = [
        "alpha_all" => self::OPTION_ALPHA_ALL,
        "alpha_high" => self::OPTION_ALPHA_HIGH,
        "alpha_low" => self::OPTION_ALPHA_LOW,
        "numeric" => self::OPTION_NUMERIC,
        "numeric_safe" => self::OPTION_NUMERIC_SAFE,
        "alpha_safe_all" => self::OPTION_ALPHA_SAFE_ALL,
        "alpha_safe_high" => self::OPTION_ALPHA_SAFE_HIGH,
        "alpha_safe_low" => self::OPTION_ALPHA_SAFE_LOW,
        "letters_low" => self::OPTION_LETTERS_LOW,
    ];

    static private function getFactory(): Factory
    {
        if(!isset(static::$FACTORY)){
            static::$FACTORY = new Factory();
            static::$STRENGTH = new Strength(Strength::MEDIUM);
        }
        return static::$FACTORY;
    }

    static public function getGenerator(): Generator
    {
        if(!isset(static::$GENERATOR)){
            $factory = static::getFactory();
            static::$GENERATOR = $factory->getGenerator(static::$STRENGTH);
        }
        return static::$GENERATOR;
    }

    /**
     * @param $min int
     * @param $max int
     * @return int
     * @throws Exception
     */
    static public function getRandomInt(int $min = 0,int $max = PHP_INT_MAX): int
    {
        return static::getGenerator()->generateInt($min,$max);
    }


    const INVALID_OPTION_MSG = "invalid option:";

    /**
     * @param $option string
     * @return string
     * @throws Exception
     */
    static public function getCharSet(string $option): string{
        if(!in_array($option,self::$OPTION)) Assert::throw(self::INVALID_OPTION_MSG.$option);

        switch ($option){

            case self::OPTION_ALPHA_ALL:
                return self::ALPHABET_HIGH . self::ALPHABET_LOW . self::NUMERIC;

            case self::OPTION_ALPHA_HIGH:
                return self::ALPHABET_HIGH . self::NUMERIC;

            case self::OPTION_ALPHA_LOW:
                return self::ALPHABET_LOW . self::NUMERIC;

            case self::OPTION_NUMERIC:
                return self::NUMERIC;

            case self::OPTION_NUMERIC_SAFE:
                return self::NUMERIC_SAFE;

            case self::OPTION_ALPHA_SAFE_ALL:
                return self::ALPHABET_SAFE_HIGH . self::ALPHABET_SAFE_LOW . self::NUMERIC_SAFE;

            case self::OPTION_ALPHA_SAFE_HIGH:
                return self::ALPHABET_SAFE_HIGH . self::NUMERIC_SAFE;

            case self::OPTION_ALPHA_SAFE_LOW:
                return self::ALPHABET_SAFE_LOW . self::NUMERIC_SAFE;

            case self::OPTION_LETTERS_LOW:
                return self::ALPHABET_LOW;

            default:
                Assert::throw("$option is not yet implemented");
        }

        return "";
    }

    /**
     * @throws Exception
     */
    static public function getRandomStr(int $length = self::DEFAULT_LENGTH, string $options = self::OPTION_ALPHA_ALL): string
    {
        Assert::isGreaterThan($length);
        $charSets = self::getCharSet($options);
        $maxCharLength = strlen($charSets);
        $randomString = "";
        for($i = 0; $i < $length; $i++){
            $randomString .= $charSets[self::getRandomInt(0,$maxCharLength-1)];
        }
        return $randomString;
    }
}