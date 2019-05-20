<?php


namespace Vctls\IntervalGraph\Util;

use DateInterval;
use DateTime;
use Exception;
use Vctls\IntervalGraph\IntervalGraph;

/**
 * Utility class for creating interval graphs.
 * 
 * @package Vctls\IntervalGraph
 */
class Date
{
    /**
     * Create an interval of dates from two integers and a value.
     *
     * @param $start
     * @param $end
     * @param mixed $value
     * @return array
     */
    public static function intv(int $start, int $end, $value = null)
    {
        try {
            $start = new DateTime("today + $start days");
            $end = (new DateTime("today + $end days"))->setTime(23,59,59);
        } catch (Exception $e) {}
        
        return [$start, $end, $value];
    }

    /**
     * Create an IntervalGraph for handling dates.
     *
     * @param $intervals
     * @return IntervalGraph
     */
    public static function intvg($intervals = null) {
        $intvg = new IntervalGraph();
        $intvg->setSubstractStep(function (DateTime $bound) {
            return (clone $bound)->sub(new DateInterval('PT1S'));
        })
            ->setAddStep(function (DateTime $bound) {
            return (clone $bound)->add(new DateInterval('PT1S'));
        });
        if (isset($intervals)) {
            $intvg->setIntervals($intervals);
        }
        return $intvg;
    }

    /**
     * Generate a random date.
     * 
     * @param int $min
     * @param int $max
     * @return DateTime
     */
    public static function rdm($min = 1514764800, $max = 1577750400)
    {
        try {
            return $date = (new DateTime)->setTimestamp(mt_rand($min, $max));
        } catch (Exception $e) {
            return null;
        }
    }
}