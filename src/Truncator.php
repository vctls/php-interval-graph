<?php

namespace Vctls\IntervalGraph;


use InvalidArgumentException;

/**
 * Truncate an array of intervals according to a low and high bound.
 *
 * @package Vctls\IntervalGraph
 */
class Truncator implements TruncatorInterface
{
    /**
     * Truncate all intervals to the given lower and upper limits.
     *
     * @param array $intervals
     * @param mixed $lowerLimit
     * @param mixed $upperLimit
     * @param bool $padding Add null value intervals between the bounds and the first and last bounds.
     * @return array
     */
    public static function truncate(array $intervals, $lowerLimit = null, $upperLimit = null, $padding = false): array
    {
        $limits = [
            0 => $lowerLimit,
            1 => $upperLimit
        ];
        foreach ($limits as $type => $limit) {
            if (isset($limit)) {
                foreach ($intervals as $key => $interval) {
                    if (self::compareToLimit($interval[$type], $limit, $type)) {
                        if (self::compareToLimit($interval[(int)!$type], $limit, $type)) {
                            // If both bounds are beyond the limit, set the interval to false for removal.
                            $intervals[$key] = false;
                        } else {
                            // If only the outer bound is beyond the limit, set it to the limit value.
                            $intervals[$key][$type] = $limit;
                        }
                    }
                }

                // Remove false elements.
                $intervals = array_filter($intervals);

                // If padding is required and a limit is set and is beyond the limit,
                // add a valueless interval between that bound and the limit.
                if ($padding) {
                    self::pad($intervals, $limit, $type);
                }
            }
        }

        return $intervals;
    }

    /**
     * Pad the interval array if needed, from left OR right depending on the type.
     *
     * @param $intervals
     * @param $value
     * @param $type
     */
    public static function pad(&$intervals, $value, $type): void
    {
        switch ($type) {
            case 0:
                $bound = self::minBound($intervals);
                if (isset($bound) && $bound > $value) {
                    array_unshift($intervals, [$value, $bound]);
                }
                break;
            case 1:
                $bound = self::maxBound($intervals);
                if (isset($bound) && $bound < $value) {
                    $intervals[] = [$bound, $value];
                }
                break;
            default:
                throw new InvalidArgumentException('Type should be 0 (low padding) or 1 (high padding).');
        }
    }

    /**
     * Return the minimum or maximum bound depending on the type.
     *
     * @param $intervals
     * @param int $type 0 (min) or 1 (max)
     * @return mixed
     */
    public static function outerBound($intervals, int $type)
    {
        switch ($type) {
            case 0:
                return self::minBound($intervals);
            case 1:
                return self::maxBound($intervals);
            default:
                throw new InvalidArgumentException('Type must be 0 (min) or 1 (max)');
        }
    }

    /**
     * Get the minimum bound in an array of intervals.
     *
     * @param $intervals
     * @return mixed
     */
    public static function minBound($intervals)
    {
        $bounds = array_column($intervals, 0);
        sort($bounds);
        return array_shift($bounds);
    }

    /**
     * Get the maximum bound in an array of intervals.
     *
     * @param $intervals
     * @return mixed
     */
    public static function maxBound($intervals)
    {
        $bounds = array_column($intervals, 1);
        sort($bounds);
        return array_pop($bounds);
    }

    /**
     * Checks if the value is inferior to a lower (type 0) limit,
     * or superior to an upper (type 1) limit.
     *
     * @param $value
     * @param $limit
     * @param int $type 0 (lower limit) or 1 (upper limit)
     * @return bool
     */
    public static function compareToLimit($value, $limit, int $type): bool
    {
        switch ($type) {
            case 0 :
                return $value < $limit;
            case 1 :
                return $value > $limit;
            default :
                throw new InvalidArgumentException('Limit type must be 0 (lower) or 1 (upper)');
        }
    }
}
