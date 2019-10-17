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
    public static function intv(int $start, int $end, $value = null): array
    {
        $startDate = DateTime::createFromFormat('Y-m-d', '2019-01-01')
            ->add(new DateInterval("P{$start}D"))->setTime(0, 0);
        $endDate = DateTime::createFromFormat('Y-m-d', '2019-01-01')
            ->add(new DateInterval("P{$end}D"))->setTime(23, 59, 59);
        return [$startDate, $endDate, $value];
    }

    /**
     * Create an IntervalGraph for handling dates.
     *
     * @param $intervals
     * @return IntervalGraph
     */
    public static function intvg($intervals = null): IntervalGraph
    {
        $intvg = new IntervalGraph();
        $intvg->getFlattener()
            ->setSubstractStep(static function (DateTime $bound) {
                return (clone $bound)->sub(new DateInterval('PT1S'));
            })
            ->setAddStep(static function (DateTime $bound) {
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
    public static function rdm($min = 1514764800, $max = 1577750400): ?DateTime
    {
        try {
            return (new DateTime)->setTimestamp(random_int($min, $max));
        } catch (Exception $e) {
            return null;
        }
    }
}
