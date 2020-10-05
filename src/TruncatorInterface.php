<?php

namespace Vctls\IntervalGraph;

interface TruncatorInterface
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
    public static function truncate(array $intervals, $lowerLimit = null, $upperLimit = null, $padding = false): array;

    /**
     * Pad the interval array if needed, from left OR right depending on the type.
     *
     * @param $intervals
     * @param $value
     * @param $type
     */
    public static function pad(&$intervals, $value, $type): void;

    /**
     * Return the minimum or maximum bound depending on the type.
     *
     * @param $intervals
     * @param int $type 0 (min) or 1 (max)
     * @return mixed
     */
    public static function outerBound($intervals, int $type);

    /**
     * Get the minimum bound in an array of intervals.
     *
     * @param $intervals
     * @return mixed
     */
    public static function minBound($intervals);

    /**
     * Get the maximum bound in an array of intervals.
     *
     * @param $intervals
     * @return mixed
     */
    public static function maxBound($intervals);

    /**
     * Checks if the value is inferior to a lower (type 0) limit,
     * or superior to an upper (type 1) limit.
     *
     * @param $value
     * @param $limit
     * @param int $type 0 (lower limit) or 1 (upper limit)
     * @return bool
     */
    public static function compareToLimit($value, $limit, int $type): bool;
}
