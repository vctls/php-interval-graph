<?php

namespace Vctls\IntervalGraph;

/**
 * A class to manipulate and display arrays of weighted intervals.
 */
class IntervalGraph implements \JsonSerializable
{
    /** @var array Initial intervals */
    protected $intervals;

    /** @var array Processed values */
    protected $values;

    /** @var string Path to the template used for rendering. */
    protected $template = 'template.php';

    /** @var \Closure Return a numeric value from the inital bound value. */
    protected $boundToNumericFunction;

    /** @var \Closure Return a string from the initial bound value. */
    protected $boundToStringFunction;

    /** @var \Closure Return a numeric value from an initial interval value. */
    protected $valueToNumericFunction;

    /** @var \Closure Return a string value from an initial interval value. */
    protected $valueToStringFunction;

    /** @var \Closure Aggregate interval values. */
    protected $aggregateFunction;

    /** @var Palette */
    protected $palette;

    /**
     * Create an IntervalGraph from intervals carrying values.
     *
     * @param array[] $intervals An array of intervals,
     * with a low bound, high bound and a value.
     */
    public function __construct($intervals = null)
    {
        if (isset($intervals)) {
            $this->setIntervals($intervals);
        }

        $this->boundToStringFunction = function (\DateTime $bound) {
            return $bound->format("Y-m-d");
        };

        $this->boundToNumericFunction = function (\DateTime $bound) {
            return $bound->getTimestamp();
        };

        $this->valueToNumericFunction = function ($v) {
            return $v === null ? null : (int)($v * 100);
        };

        $this->valueToStringFunction = function ($v) {
            return $v === null ? null : ($v * 100 . '%');
        };

        $this->aggregateFunction = function ($a, $b) {
            if ($a === null && $b === null) {
                return null;
            }
            return round($a + $b, 2);
        };

        $this->palette = new Palette();
    }

    /**
     * Check that an array of intervals is correctly formatted.
     *
     * The first element must be the low bound.
     *
     * The second element must be the high bound.
     *
     * The third element must be the value.
     *
     * Inverted end and low bounds will be put back in chronological order.
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

            // Ensure start and high bounds are in the right order.
            if ($i[0] > $i [1]) {
                $a = $i[0];
                $intervals[$k][0] = $i[1];
                $intervals[$k][1] = $a;
            }
        }
    }

    /**
     * Truncate all intervals to the given start and high bounds.
     *
     * @param array $intervals
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool $padding Add null value intervals between the bounds and the first and last date.
     * @return array
     */
    public static function truncate(array $intervals, \DateTime $start = null, \DateTime $end = null, $padding = false)
    {
        // Ensure the $intervals array is well formatted.
        self::checkFormat($intervals);

        if (isset($start)) {
            $intervals = array_map(function ($i) use ($start) {
                if ($i[0] < $start) { // If the low bound is before the lower bound...
                    if ($i[1] < $start) {
                        // ... and the high bound is also before the lower bound, set the interval to false.
                        $i = false;
                    } else {
                        // If only the low bound is before the lower bound, set it to the bound value.
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
                        // If only the high bound is after the upper bound, set it to the bound value.
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
     * Render an HTML view of the intervalGraph.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $html = $this->draw();
        } catch (\Exception $e) {
            $html = "Error : " . $e->getMessage();
        }
        return $html;
    }

    /**
     * Render an HTML view of the intervalGraph.
     *
     * @return string
     */
    public function draw()
    {
        if (!isset($this->values)) {
            $this->process();
        }
        $vs = $this->values;
        ob_start();
        include $this->template;
        $html = ob_get_clean();
        // Remove all surplus whitespace.
        $html = preg_replace(['/(?<=>)\s+/', '/\s+(?=<)/', '/\s+/'], ['', '', ' '], $html);
        return $html;
    }

    /**
     * Process intervals and store processed values.
     *
     * @return IntervalGraph
     */
    public function process()
    {
        $intervals = $this->getFlatIntervals();

        // Extract values.
        $t = array_column($intervals, 2);

        // Change bounds to numeric values.
        $values = array_map(function (array $i) {
            return [
                ($this->boundToNumericFunction)($i[0]),
                ($this->boundToNumericFunction)($i[1]),
            ];
        }, $intervals);

        // Order by low bound.
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

        // Order by high bound.
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

        // Put values back in, along with the formatted date.
        // Since we're using associative sorting functions, we know the keys haven't changed.
        $values = array_map(function ($k, array $i) use ($t, $intervals) {
            if ($intervals[$k][0] === $intervals[$k][1]) {
                return [
                    $i[0], // Single value position percentage
                    ($this->boundToStringFunction)($intervals[$k][0]), // Signle value string
                ];
            } else {
                return [
                    $i[0], // Interval start percentage
                    100 - $i[1], // Interval end percentage from right
                    // Note: for some reason, using 'widht' instead of 'right'
                    // causes the right border to be hidden underneath the next interval.
                    !empty($t) ? $this->palette->getColor(
                        isset($t[$k]) ? ($this->valueToNumericFunction)($t[$k]) : null
                    ) : 50, // Interval color
                    ($this->boundToStringFunction)($intervals[$k][0]), // Interval start string value
                    ($this->boundToStringFunction)($intervals[$k][1]), // Interval end string value
                    !empty($t) ? (isset($t[$k]) ? ($this->valueToStringFunction)($t[$k]) : null) : null,// Interval string value
                ];
            }
        }, array_keys($values), $values);

        // Put isolated dates at the end.
        usort($values, function ($i) {
            return count($i) === 2 ?  1 : -1;
        });

        $this->values = $values;

        return $this;
    }

    /**
     * Transform an array of intervals with possible overlapping
     * into an array of adjacent intervals with no overlapping.
     *
     * @return array
     */
    public function getFlatIntervals()
    {
        // Extract isolated dates.
        $isolatedDates = self::extractDates($this->intervals);

        $dates = self::intervalsToSignedDates($this->intervals);

        $flat = $this->calcNewIntervals($dates);

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
     * Make an array of dates from an array of intervals.
     * Assign the value of the interval to each date.
     * Assign and a '+' sign if it is a low bound, and a '-' if it is an high bound.
     *
     * @param $intervals
     * @return array
     */
    public static function intervalsToSignedDates($intervals)
    {
        $dates = [];
        foreach ($intervals as $key => $interval) {
            $dates[] = [$interval[0], isset($interval[2]) ? $interval[2] : null, '+', $key];
            $dates[] = [$interval[1], isset($interval[2]) ? $interval[2] : null, '-', $key];
        }
        // Order the dates.
        usort($dates, function (array $d1, array $d2) {
            return ($d1[0] < $d2[0]) ? -1 : 1;
        });
        return $dates;
    }

    /**
     * Create each new interval and calculate its value based on the active intervals on each date.
     *
     * @param $dates
     * @return array
     */
    public function calcNewIntervals($dates)
    {
        // Get the values of the original intervals, including nulls.
        $origIntVals = array_map(function ($interval) {
            return isset($interval[2]) ? $interval[2] : null;
        }, $this->intervals);

        $newIntervals = [];
        $activeIntervals = [];

        // Create new intervals for each set of two consecutive dates,
        // and calculate its total value.
        for ($i = 1; $i < count($dates); $i++) {

            // Set the current date.
            $curDate = $dates[$i - 1];

            if ($curDate[2] === '+') {
                // If this is a low bound,
                // add the key of the interval to the array of active intervals.
                $activeIntervals[$curDate[3]] = true;
            } else {
                // If this is an high bound, remove the key.
                unset($activeIntervals[$curDate[3]]);
            }

            if (empty($activeIntervals)) {
                // If no intervals are active on this date,
                // the value of this interval is null.
                $ival = null;
            } else {
                // Else, aggregate the values of the corresponding intervals.
                $ival = array_reduce(
                    array_intersect_key($origIntVals, $activeIntervals),
                    $this->aggregateFunction
                );
            }

            $newIntervals[] = [$curDate[0], $dates[$i][0], $ival];
        }

        return $newIntervals;
    }

    /**
     * Define the function to convert the interval values to a numeric value
     * in order to match them to a color on the palette.
     *
     * @param \Closure $valueToNumericFunction
     * @return IntervalGraph
     */
    public function setValueToNumericFunction(\Closure $valueToNumericFunction)
    {
        $this->valueToNumericFunction = $valueToNumericFunction;
        return $this;
    }

    /**
     * Define the  function to convert the interval values to strings
     * in order to display them in the view.
     *
     * @param \Closure $valueToStringFunction
     * @return IntervalGraph
     */
    public function setValueToStringFunction(\Closure $valueToStringFunction)
    {
        $this->valueToStringFunction = $valueToStringFunction;
        return $this;
    }

    /**
     * Define the function to aggregate interval values.
     *
     * @param \Closure $aggregateFunction
     * @return IntervalGraph
     */
    public function setAggregateFunction(\Closure $aggregateFunction)
    {
        $this->aggregateFunction = $aggregateFunction;
        return $this;
    }

    /**
     * Set the function to convert interval bound values to string.
     *
     * @param \Closure $boundToStringFunction
     * @return IntervalGraph
     */
    public function setBoundToStringFunction($boundToStringFunction)
    {
        $this->boundToStringFunction = $boundToStringFunction;
        return $this;
    }

    /**
     * @return array
     */
    public function getIntervals()
    {
        return $this->intervals;
    }

    /**
     * Set the intervals to be processed.
     *
     * If another set of intervals was previously processed,
     * the processed values will be deleted.
     *
     * @param array $intervals
     * @return IntervalGraph
     */
    public function setIntervals(array $intervals)
    {
        self::checkFormat($intervals);
        $this->intervals = $intervals;
        $this->values = null;
        return $this;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set the PHP template to use for rendering.
     *
     * @param string $template
     * @return IntervalGraph
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return Palette
     */
    public function getPalette()
    {
        return $this->palette;
    }

    /**
     * @param Palette $palette
     * @return IntervalGraph
     */
    public function setPalette($palette)
    {
        $this->palette = $palette;
        return $this;
    }

    /**
     * @param \Closure $boundToNumericFunction
     * @return IntervalGraph
     */
    public function setBoundToNumericFunction($boundToNumericFunction)
    {
        $this->boundToNumericFunction = $boundToNumericFunction;
        return $this;
    }

    /**
     * Return the array of values to be serialized by json_encode.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        if (!isset($this->values)) {
            $this->process();
        }
        return $this->values;
    }
}