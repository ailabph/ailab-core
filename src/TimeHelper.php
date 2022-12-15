<?php

namespace Ailabph\AilabCore;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception;

class TimeHelper
{
    public Carbon $current;
    public Carbon $from;
    public Carbon $to;

    # ----------------------------------------------------------------------------------------------------------------

    const DEFAULT_TIMEZONE = "Asia/Manila";
    const DEFAULT_START_OF_WEEK = CarbonInterface::SUNDAY;
    const DEFAULT_END_OF_WEEK = CarbonInterface::SATURDAY;

    const FORMAT_DATE = "Y-m-d";
    const FORMAT_DATE_TIME_DB = "Y-m-d H:i:s";
    const FORMAT_DATE_TIME_AMPM = "Y-m-d h:i A";

    public static string|int $OVERRIDE_CURRENT_TIME = 0;

    /**
     * @throws Exception
     */
    static public function getTimeZone(): string{
        if(config::getCustomOption("default_time_zone")){
            return config::getCustomOption("default_time_zone");
        }
        return self::DEFAULT_TIMEZONE;
    }

    # region HELPERS

    /**
     * @throws Exception
     */
    static public function isValidDate(string|int $date, bool $throwError = true): Carbon | false{
        try{
            $carbon = Carbon::parse($date,self::getTimeZone());
            $carbon->setTimezone(self::getTimeZone());
            return $carbon;
        }
        catch (Exception $e){
            if($throwError) Assert::throw("$date is not a valid date format");
            return false;
        }
    }

    # endregion

    /**
     * @throws Exception
     */
    static public function getCurrentTime(): Carbon{
        if(self::$OVERRIDE_CURRENT_TIME > 0){
            $carbon = self::isValidDate(self::$OVERRIDE_CURRENT_TIME);
        }
        else{
            $carbon = Carbon::now(self::getTimeZone());
            $carbon->setTimezone(self::getTimeZone());
        }
        return $carbon;
    }

    /**
     * @throws Exception
     */
    static public function getAsFormat(Carbon|string|int $time, string $format = CarbonInterface::DEFAULT_TO_STRING_FORMAT) : string{
        $time = self::getTimeAsCarbon($time);
        return $time->format($format);
    }

    /**
     * @throws Exception
     */
    static public function getTimeAsCarbon(Carbon|string|int $time): Carbon{
        if($time instanceof Carbon){
            return $time;
        }
        return self::isValidDate($time);
    }


    # region RANGES

    /**
     * @throws Exception
     */
    static public function createHourlyRange(Carbon|string|int $time) : TimeHelper{
        $time = self::getTimeAsCarbon($time);
        $range = new self();
        $range->from = $time->copy()->startOfHour();
        $range->to = $time->copy()->endOfHour();
        $range->current = $time->copy();
        return $range;
    }

    /**
     * @throws Exception
     */
    static public function createDailyRange(Carbon|string|int $time) : TimeHelper{
        $time = self::getTimeAsCarbon($time);
        $range = new self();
        $range->from = $time->copy()->startOfDay();
        $range->to = $time->copy()->endOfDay();
        $range->current = $time->copy();
        return $range;
    }

    /**
     * @throws Exception
     */
    static public function createWeeklyRange(Carbon|string|int $time, int $start_of_week = self::DEFAULT_START_OF_WEEK, int $end_of_week = self::DEFAULT_END_OF_WEEK) : TimeHelper{
        $time = self::getTimeAsCarbon($time);
        $range = new self();
        $range->from = $time->copy()->startOfWeek($start_of_week);
        $range->to = $time->copy()->endOfWeek($end_of_week);
        $range->current = $time->copy();
        return $range;
    }

    /**
     * @throws Exception
     */
    static public function createMonthlyRange(Carbon|string|int $time) : TimeHelper{
        $time = self::getTimeAsCarbon($time);
        $range = new self();
        $range->from = $time->copy()->startOfMonth();
        $range->to = $time->copy()->endOfMonth();
        $range->current = $time->copy();
        return $range;
    }

    # endregion


    /**
     * @throws Exception
     */
    static public function getStartOfDay(Carbon|string|int $time): Carbon
    {
        $carbon = self::getTimeAsCarbon($time);
        return $carbon->startOfDay();
    }

    /**
     * @throws Exception
     */
    static public function getEndOfDay(Carbon|string|int $time): Carbon
    {
        $carbon = self::getTimeAsCarbon($time);
        return $carbon->endOfDay();
    }

    /**
     * @throws Exception
     */
    static public function getStartOfWeek(Carbon|string|int $time, int $start_of_week = self::DEFAULT_START_OF_WEEK): Carbon{
        return self::getTimeAsCarbon($time)->startOfWeek($start_of_week);
    }

    /**
     * @throws Exception
     */
    static public function getEndOfWeek(Carbon|string|int $time, int $end_of_week = self::DEFAULT_END_OF_WEEK): Carbon
    {
        $carbon = self::getTimeAsCarbon($time);
        return $carbon->endOfWeek($end_of_week);
    }

    /**
     * @throws Exception
     */
    static public function getStartOfMonth(Carbon|string|int $time): Carbon
    {
        return self::getTimeAsCarbon($time)->startOfMonth();
    }

    /**
     * @throws Exception
     */
    static public function getEndOfMonth(Carbon|string|int $time): Carbon
    {
        return self::getTimeAsCarbon($time)->endOfMonth();
    }
}