<?php

use PHPUnit\Framework\TestCase;
use Ailabph\AilabCore;

class AssertTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testIsNotEmptyThrowsException(){
        self::expectExceptionMessage(AilabCore\Assert::IS_NOT_EMPTY_MSG);
        AilabCore\Assert::isNotEmpty("");
    }

    /**
     * @throws Exception
     */
    public function testIsNotEmptyReturnsTrue(){
        self::assertTrue(AilabCore\Assert::isNotEmpty("abc"));
    }

    /**
     * @throws Exception
     */
    public function testIsGreaterThanThrowsException(){
        self::expectExceptionMessage(AilabCore\Assert::IS_GREATER_THAN_MSG);
        AilabCore\Assert::isGreaterThan("0");
    }

    /**
     * @throws Exception
     */
    public function testIsGreaterThanCustomThrowsException(){
        self::expectExceptionMessage(AilabCore\Assert::IS_GREATER_THAN_MSG);
        AilabCore\Assert::isGreaterThan("10",100);
    }

    /**
     * @throws Exception
     */
    public function testIsNumericThrowsException(){
        self::expectExceptionMessage(AilabCore\Assert::IS_NOT_NUMERIC_MSG);
        AilabCore\Assert::isNumeric("abc");
    }

    /**
     * @throws Exception
     */
    public function testIsNumericReturnsTrue(){
        self::assertTrue(AilabCore\Assert::isNumeric("123"));
    }
}