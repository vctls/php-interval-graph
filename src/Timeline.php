<?php

/**
 * A Timeline class to manipulate and visualize arrays of dates and weighted date intervals in chronological order.
 */
class Timeline
{
    protected $values;

    protected $date_format = "Y-m-d";

    /**
     * @var array $palette An array of percentages with corresponding color codes.
     *
     */
    protected $palette = [
        [0, '#ff5450'],
        [0, '#ff5450'],
        [50, '#ff9431'],
        [100, '#d7e174'],
        [100, '#5cb781'],
        [100, '#557ebf'],
    ];

    protected $bgColor = '#e1e0eb';

    /**
     * Set the color palette for percent ranges.
     *
     * Ranges should be simple arrays, containing only the upper bound and the corresponding color value, like this:
     *  [ 50, '#ff9431' ]
     *
     * For discrete values, simply insert the same value twice.
     *
     * @param array $palette
     * @return Timeline
     */
    public function setPalette(array $palette)
    {
        usort($palette, function ($p1, $p2) {
            return $p2[0] - $p1[0];
        });
        $this->palette = $palette;
        return $this;
    }

    public function setBGColor($color)
    {
        $this->bgColor = $color;
        return $this;
    }

    /**
     * Get the hexadecimal color code for the given percentage.
     *
     * @param integer $percent
     * @return string
     */
    public function getColor($percent)
    {
        $palette = $this->palette;
        if ($percent === null) {
            return isset($this->bgColor) ? $this->bgColor : '';
        }
        for ($i = 0; $i < count($palette); $i++) {
            if (
                $i === 0 && $percent < $palette[$i][0]
                || $i > 0 && $palette[$i][0] === $palette[$i - 1][0] && $percent === $palette[$i][0]
                || $i > 0 && $palette[$i][0] !== $palette[$i - 1][0] && $percent < $palette[$i][0]
                || $i === (count($palette) - 1) && $percent > $palette[$i][0]
            ) {
                return $palette[$i][1];
            }
        }
        throw new \LogicException("The percentage $percent did not match any range in the color palette.");
    }

    /**
     * Create a timeline from weighed intervals.
     *
     * @param array[] $intervals An array of weighted date intervals, with a start date, end date and a weight from 0 to 1
     * @param string $date_format The output date format.
     */
    public function __construct($intervals, $date_format = "Y-m-d")
    {
        $this->date_format = $date_format;
        $intervals = self::flatten($intervals);

        // Extract weights.
        $t = array_column($intervals, 2);


        // Change dates to timestamps.
        $values = array_map(function (array $i) {
            return [$i[0]->getTimestamp(), $i[1]->getTimeStamp()];
        }, $intervals);

        // Order by start date.
        uasort($values, function (array $i1, array $i2) {
            return ($i1[0] < $i2[0]) ? -1 : 1;
        });

        // Get the min timestamp.
        $min = reset($values)[0];

        // Substract min from all timestamps.
        $values = array_map(function ($i) use ($min) {
            return [
                $i[0] - $min,
                $i[1] - $min
            ];
        }, $values);

        // Order by end date.
        uasort($values, function (array $i1, array $i2) {
            return ($i1[1] < $i2[1]) ? -1 : 1;
        });

        // Get max timestamp.
        $max = end($values)[1];

        // Calculate percentages.
        $values = array_map(function (array $i) use ($max) {
            return array_map(function ($int) use ($max) {
                return round($int * 100 / $max);
            }, $i);
        }, $values);

        // Put weights back in, along with the formatted date.
        // Since we're using associative sorting functions, we know the keys haven't changed.
        $values = array_map(function ($k, array $i) use ($t, $intervals) {
            return [
                $i[0],
                $i[1],
                !empty($t) ? (isset($t[$k]) ? (int)($t[$k] * 100) : null) : 50,
                $intervals[$k][0],
                $intervals[$k][1]
            ];
        }, array_keys($values), $values);

        // Put isolated dates at the end.
        uasort($values, function ($i) {
            return $i[0] === $i[1] ? 1 : -1;
        });

        $this->values = $values;
    }


    /**
     * Transform an array of weighted date intervals with possible overlapping
     * into an array of adjacent weighted intervals with no overlapping.
     *
     * @param array $intervals An array of weighted date intervals, with a start date, end date and a weight from 0 to 1
     * @return array
     */
    public static function flatten(array $intervals)
    {
        self::checkFormat($intervals);

        // Extract isolated dates.
        $isolatedDates = self::extractDates($intervals);

        $dates = self::intervalsToSignedDates($intervals);

        // Order the dates.
        usort($dates, function (array $d1, array $d2) {
            return ($d1[0] < $d2[0]) ? -1 : 1;
        });

        $flat = [];
        // Create new intervals for each set of two consecutive dates,
        // and calculate its total weight.
        for ($i = 1; $i < count($dates); $i++) {
            // The weight of the new interval is the sum of the value of each date before its end date.
            $previousDates = array_slice($dates, 0, $i);

            // Count the number of previous '+' with non null weights.
            $pluses = count(array_filter($previousDates, function ($date) {
                return $date[1] !== null && $date[2] === '+';
            }));

            // Count the number of previous '-' with non null weights.
            $minuses = count(array_filter($previousDates, function ($date) {
                return $date[1] !== null && $date[2] === '-';
            }));

            $ival = array_reduce($previousDates,
                function ($a, $b) {
                    return $a + ($b[2] === '+' ? $b[1] : -$b[1]);
                });

            // TODO Find a cleaner method of preventing decimal errors.
            $ival = round($ival, 2);

            if ($pluses == $minuses) {
                if ($ival != 0) {
                    throw new \LogicException(
                        "Although there are as many start and end dates, the sum of weights is different than 0:"
                        . " $ival"
                    );
                }
                $ival = null;
            }

            $flat[] = [$dates[$i - 1][0], $dates[$i][0], $ival];
        }

        // Remove empty interval generated when two or more intervals share a common date.
        $flat = array_values(array_filter($flat, function ($i) {
            return $i[0] !== $i[1];
        }));


        // Push isolated dates back into the array.
        if (!empty($isolatedDates)) {
            array_push($flat, ...$isolatedDates);
        }

        return $flat;
    }

    /**
     * Make an array of dates from an array of intervals.
     * Assign the value of the weight to each date.
     * Assign and a '+' sign if it is a start date, and a '-' if it is an end date.
     *
     * @param $intervals
     * @return array
     */
    public static function intervalsToSignedDates($intervals)
    {
        $dates = [];
        foreach ($intervals as $interval) {
            $dates[] = [$interval[0], isset($interval[2]) ? $interval[2] : null, '+'];
            $dates[] = [$interval[1], isset($interval[2]) ? $interval[2] : null, '-'];
        }
        return $dates;
    }

    /**
     * Get the maximum date in an array of intervals.
     *
     * @param $intervals
     * @return \DateTime
     */
    public static function maxDate($intervals)
    {
        self::checkFormat($intervals);
        $dates = array_column($intervals, 1);
        sort($dates);
        return array_pop($dates);
    }

    /**
     * Get the minimum date in an array of intervals.
     *
     * @param $intervals
     * @return \DateTime
     */
    public static function minDate($intervals)
    {
        self::checkFormat($intervals);
        $dates = array_column($intervals, 0);
        sort($dates);
        return array_shift($dates);
    }

    /**
     * Extract isolated dates from an array of intervals.
     *
     * Intervals with the exact same start and end date will be considered as isolated dates.
     *
     * They will be removed from the initial array, and returned in a separate array.
     *
     * @param array $intervals The initial array.
     * @return array An array containing only isolated dates.
     */
    public static function extractDates(array &$intervals)
    {
        $dates = array_filter($intervals, function ($interval) {
            return $interval[0] === $interval[1];
        });

        $intervals = array_diff_key($intervals, $dates);

        return $dates;
    }

    /**
     * Sets the date format used in the data-title attribute of the timeline HTML.
     *
     * @param $format
     */
    public function setDateFormat($format)
    {
        $this->date_format = $format;
    }

    /**
     * Truncate all intervals to the given start and end dates.
     *
     * @param array $intervals
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool $padding Add null weighted intervals between the bounds and the first and last date.
     * @return array
     */
    public static function truncate(array $intervals, \DateTime $start = null, \DateTime $end = null, $padding = false)
    {
        // Ensure the $intervals array is well formatted.
        self::checkFormat($intervals);

        if (isset($start)) {
            $intervals = array_map(function ($i) use ($start) {
                if ($i[0] < $start) { // If the start date is before the lower bound...
                    if ($i[1] < $start) {
                        // ... and the end date is also before the lower bound, set the interval to false.
                        $i = false;
                    } else {
                        // If only the start date is before the lower bound, set it to the bound value.
                        $i[0] = $start;
                    }
                }
                return $i;
            }, $intervals);

            // Remove false elements.
            $intervals = array_filter($intervals);

            // If padding is required and a lower bound is set and inferior to the min date,
            // add a wightless interval between that date and the bound.
            if ($padding) {
                $minDate = self::minDate($intervals);
                if ($minDate > $start) {
                    $intervals[] = [$start, $minDate];
                }
            }
        }

        // TODO DRY
        if (isset($end)) {
            $intervals = array_map(function ($i) use ($end) {
                if ($i[1] > $end) {
                    if ($i[0] > $end) {
                        // If both dates are after the given upper bound, set the interval to false.
                        $i = false;
                    } else {
                        // If only the end date is after the upper bound, set it to the bound value.
                        $i[1] = $end;
                    }
                }
                return $i;
            }, $intervals);

            // Remove false elements.
            $intervals = array_filter($intervals);

            // If padding is required and a higher bound is set and superior to the max date,
            // add a wightless interval between that date and the bound.
            if ($padding) {
                $maxDate = self::maxDate($intervals);
                if ($maxDate < $end) {
                    $intervals[] = [$end, $maxDate];
                }
            }
        }

        return $intervals;
    }

    /**
     * Check that an array of intervals is correctly formatted.
     *
     * The first element must be the start date.
     *
     * The second element must be the end date.
     *
     * The third element must be the weight. It must be numeric or null.
     *
     * Inverted end and start dates will be put back in chronological order.
     *
     * @param array &$intervals
     */
    public static function checkFormat(array &$intervals)
    {
        foreach ($intervals as $k => $i) {

            if (!is_array($i)) {
                $t = gettype($i);
                throw new \InvalidArgumentException(
                    "Each element of the '\$intervals' array should be an array, $t given."
                );
            }

            if (!$i[0] instanceof \DateTime) {
                $t = gettype($i[0]);
                throw new \InvalidArgumentException(
                    "The first element of an interval array should be an instance of DateTime, $t given."
                );
            }

            if (!$i[1] instanceof \DateTime) {
                $t = gettype($i[1]);
                throw new \InvalidArgumentException(
                    "The second element of an interval array should be an instance of DateTime, $t given."
                );
            }

            if (isset($i[2]) && !is_numeric($i[2])) {
                $t = gettype($i[1]);
                throw new \InvalidArgumentException(
                    "The third element of an interval array should be numeric or null, $t given."
                );
            }

            // Ensure start and end dates are in the right order.
            if ($i[0] > $i [1]) {
                $a = $i[0];
                $intervals[$k][0] = $i[1];
                $intervals[$k][1] = $a;
            }
        }
    }

    /**
     * Render an HTML view of the timeline.
     *
     * @return string
     */
    public function draw()
    {
        $vs = $this->values;
        ob_start();
        ?>
        <div class="foo" style="position: relative; width: 100%; height: 20px;
        <?= isset($this->bgColor) ? ' background-color: ' . $this->bgColor . ';' : '' ?>">
            <?php foreach ($vs as $k => $v) : ?>
                <?php if ($v[3] === $v[4]): // Isolated date. ?>
                    <div class="bar bar<?= $k; ?>" style="position: absolute; height: 20px; box-sizing: content-box;
                            border-width: 0 2px 0 2px;
                            border-style: solid;
                            border-color: black;
                            left:  <?= $v[0] ?>%;
                            width: 0;"
                         data-title="<?= $v[3]->format($this->date_format) ?>"
                    >
                    </div>
                <?php else: ?>
                    <div class="bar bar<?= $k; ?>" style="position: absolute; height: 20px;
                            left:  <?= $v[0] ?>%;
                            right: <?= 100 - $v[1] ?>%;
                            /*width: <?= $v[1] - $v[0] ?>%;*/
                            background-color: <?= $this->getColor($v[2]) ?>"
                         data-title="<?=
                         $v[3]->format($this->date_format)
                         . ' ➔ ' .
                         $v[4]->format($this->date_format)
                         . ' : ' .
                         $v[2]
                         ?>%"
                    >
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        $html = ob_get_contents();
        ob_clean();
        return $html;
    }

    /**
     * Render an HTML view of the timeline.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->draw();
    }
}