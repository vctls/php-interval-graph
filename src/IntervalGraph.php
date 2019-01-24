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
    protected $boundToNumeric;

    /** @var \Closure Return a string from the initial bound value. */
    protected $boundToString;

    /** @var \Closure Return a numeric value from an initial interval value. */
    protected $valueToNumeric;

    /** @var \Closure Return a string value from an initial interval value. */
    protected $valueToString;

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

        $this->boundToNumeric = function (\DateTime $bound) {
            return $bound->getTimestamp();
        };

        $this->boundToString = function (\DateTime $bound) {
            return $bound->format("Y-m-d");
        };

        $this->valueToNumeric = function ($v) {
            return $v === null ? null : (int)($v * 100);
        };

        $this->valueToString = function ($v) {
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
     * @return $this
     */
    public function checkIntervals()
    {
        
        foreach ($this->intervals as $intervalKey => $interval) {

            // Check that the interval is an array.
            if (!is_array($interval)) {
                $t = gettype($interval);
                throw new \InvalidArgumentException(
                    "Each element of the '\$intervals' array should be an array, $t given."
                );
            }

            // Check that the bounds and value of the interval can be converted to both a numeric
            // and string value with the given closures.
            foreach ([['Lower bound', 'bound'], ['Higher bound', 'bound'], ['Value', 'value']] as $index => $property) {
                
                // Skip value property of valueless intervals.
                if ($property[1] === 'value' && !isset($interval[$index])) {
                    continue;
                }
                
                foreach (['numeric', 'string'] as $expectedType) {
                    
                    $expectedTypeTitle = ucfirst($expectedType);
                    
                    try {
                        $value = ($this->{"$property[1]To$expectedTypeTitle"})($interval[$index]);
                    } catch (\Exception $exception) {
                        throw new \InvalidArgumentException(
                            "$property[0] of interval $intervalKey cannot be converted to a $expectedType value " .
                            "with the given '$property[1]To$expectedTypeTitle' function. Error : " . $exception->getMessage()
                        );
                    }

                    $actualType = gettype($value);

                    if (!call_user_func("is_$expectedType", $value)) {
                        throw new \InvalidArgumentException(
                            "$property[0] of interval $intervalKey is not converted to a $expectedType value " .
                            "by the given '$property[1]To$expectedTypeTitle' function. Returned type : $actualType"
                        );
                    }
                }
            }

            // Ensure start and high bounds are in the right order.
            if ($interval[0] > $interval [1]) {
                $a = $interval[0];
                $intervals[$intervalKey][0] = $interval[1];
                $intervals[$intervalKey][1] = $a;
            }
        }

        // TODO Check that the values can be aggregated with the given closure.
        
        return $this;
    }

    /**
     * Truncate all intervals to the given lower and upper limits.
     *
     * @param array $intervals
     * @param mixed $lowerLimit
     * @param mixed $upperLimit
     * @param bool $padding Add null value intervals between the bounds and the first and last bounds.
     * @return array
     */
    public static function truncate(array $intervals, $lowerLimit = null, $upperLimit = null, $padding = false)
    {
        if (isset($lowerLimit)) {
            $intervals = array_map(function ($i) use ($lowerLimit) {
                if ($i[0] < $lowerLimit) { // If the low bound is before the lower bound...
                    if ($i[1] < $lowerLimit) {
                        // ... and the high bound is also before the lower bound, set the interval to false.
                        $i = false;
                    } else {
                        // If only the low bound is before the lower bound, set it to the bound value.
                        $i[0] = $lowerLimit;
                    }
                }
                return $i;
            }, $intervals);

            // Remove false elements.
            $intervals = array_filter($intervals);

            // If padding is required and a lower limit is set and is inferior to the min bound,
            // add a weightless interval between that bound and the limit.
            if ($padding) {
                $minBound = self::minBound($intervals);
                if (isset($minBound) && $minBound > $lowerLimit) {
                    $intervals[] = [$lowerLimit, $minBound];
                }
            }
        }

        // TODO DRY
        if (isset($upperLimit)) {
            $intervals = array_map(function ($i) use ($upperLimit) {
                if ($i[1] > $upperLimit) {
                    if ($i[0] > $upperLimit) {
                        // If both bounds are after the given upper limit, set the interval to false.
                        $i = false;
                    } else {
                        // If only the high bound is after the upper limit, set it to the bound value.
                        $i[1] = $upperLimit;
                    }
                }
                return $i;
            }, $intervals);

            // Remove false elements.
            $intervals = array_filter($intervals);

            // If padding is required and a upper limit is set and is superior to the max bound,
            // add a valueless interval between that bound and the limit.
            if ($padding) {
                $maxBound = self::maxBound($intervals);
                if (isset($maxBound) && $maxBound < $upperLimit) {
                    $intervals[] = [$upperLimit, $maxBound];
                }
            }
        }

        return $intervals;
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
        
        // Remove all surplus whitespace.
        return preg_replace(
            ['/(?<=>)\s+/', '/\s+(?=<)/', '/\s+/'], ['', '', ' '], 
            ob_get_clean()
        );
    }

    /**
     * Process intervals and store processed values.
     *
     * @return IntervalGraph
     */
    public function process()
    {
        $flatIntervals = $this->getFlatIntervals();

        // Extract values.
        $t = array_column($flatIntervals, 2);

        // Change bounds to numeric values.
        $numVals = array_map(function (array $i) {
            return [
                ($this->boundToNumeric)($i[0]),
                ($this->boundToNumeric)($i[1]),
            ];
        }, $flatIntervals);

        // Order by low bound.
        uasort($numVals, function (array $i1, array $i2) {
            return ($i1[0] < $i2[0]) ? -1 : 1;
        });

        // Get the min timestamp.
        $min = reset($numVals)[0];

        // Substract min from all timestamps.
        $numVals = array_map(function ($i) use ($min) {
            return [
                $i[0] - $min,
                $i[1] - $min
            ];
        }, $numVals);

        // Order by high bound.
        uasort($numVals, function (array $i1, array $i2) {
            return ($i1[1] < $i2[1]) ? -1 : 1;
        });

        // Get max timestamp.
        $max = end($numVals)[1];

        // Calculate percentages.
        $numVals = array_map(function (array $i) use ($max) {
            return array_map(function ($int) use ($max) {
                return round($int * 100 / $max);
            }, $i);
        }, $numVals);

        // Put values back in, along with the formatted bound.
        // Since we're using associative sorting functions, we know the keys haven't changed.
        $numVals = array_map(function ($k, array $i) use ($t, $flatIntervals) {
            if ($flatIntervals[$k][0] === $flatIntervals[$k][1]) {
                return [
                    $i[0], // Single value position percentage
                    ($this->boundToString)($flatIntervals[$k][0]), // Signle value string
                ];
            } else {
                $colorval = isset($t[$k]) ? ($this->valueToNumeric)($t[$k]) : null;
                $stingval = isset($t[$k]) ? ($this->valueToString)($t[$k]) : null;
                return [
                    $i[0], // Interval start percentage
                    100 - $i[1], // Interval end percentage from right
                    // Note: for some reason, using 'widht' instead of 'right'
                    // causes the right border to be hidden underneath the next interval.
                    !empty($t) ? $this->palette->getColor($colorval) : 50, // Interval color
                    ($this->boundToString)($flatIntervals[$k][0]), // Interval start string value
                    ($this->boundToString)($flatIntervals[$k][1]), // Interval end string value
                    !empty($t) ? ($stingval) : null,// Interval string value
                ];
            }
        }, array_keys($numVals), $numVals);

        // Put discrete values at the end and reset indices.
        // Reseting indices ensures the processed values are
        // serialized as correctly ordered JSON arrays.
        usort($numVals, function ($i) {
            return count($i) === 2 ? 1 : -1;
        });

        $this->values = $numVals;

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
        $discreteValues = self::extractDiscreteValues($this->intervals);
        $signedBounds = self::intervalsToSignedBounds($this->intervals);
        $adjacentIntervals = $this->calcAdjacentIntervals($signedBounds);

        // Remove empty interval generated when two or more intervals share a common bound.
        $adjacentIntervals = array_values(array_filter($adjacentIntervals, function ($i) {
            // Use weak comparison in case of object typed bounds.
            return $i[0] != $i[1];
        }));

        // Push discrete values back into the array.
        if (!empty($discreteValues)) {
            array_push($adjacentIntervals, ...$discreteValues);
        }

        return $adjacentIntervals;
    }

    /**
     * Extract discrete values from an array of intervals.
     *
     * Intervals with the exact same lower and higher bound will be considered as discrete values.
     *
     * They will be removed from the initial array, and returned in a separate array.
     *
     * @param array $intervals The initial array.
     * @return array An array containing only discrete values.
     */
    public static function extractDiscreteValues(array &$intervals)
    {
        $discreteValues = array_filter($intervals, function ($interval) {
            return $interval[0] === $interval[1];
        });

        $intervals = array_diff_key($intervals, $discreteValues);

        return $discreteValues;
    }

    /**
     * Make an array of bounds from an array of intervals.
     * 
     * Assign the value of the interval to each bound.
     * 
     * Assign and a '+' sign if it is a low bound, and a '-' if it is an high bound.
     *
     * @param $intervals
     * @return array
     */
    public static function intervalsToSignedBounds($intervals)
    {
        $bounds = [];
        foreach ($intervals as $key => $interval) {
            $bounds[] = [$interval[0], isset($interval[2]) ? $interval[2] : null, '+', $key];
            $bounds[] = [$interval[1], isset($interval[2]) ? $interval[2] : null, '-', $key];
        }
        // Order the bounds.
        usort($bounds, function (array $d1, array $d2) {
            return ($d1[0] < $d2[0]) ? -1 : 1;
        });
        return $bounds;
    }

    /**
     * Create each new interval and calculate its value based on the active intervals on each bound.
     *
     * @param $bounds
     * @return array
     */
    public function calcAdjacentIntervals($bounds)
    {
        // Get the values of the original intervals, including nulls.
        $origIntVals = array_map(function ($interval) {
            return isset($interval[2]) ? $interval[2] : null;
        }, $this->intervals);

        $newIntervals = [];
        $activeIntervals = [];

        // Create new intervals for each set of two consecutive bounds,
        // and calculate its total value.
        for ($i = 1; $i < count($bounds); $i++) {

            // Set the current bound.
            $curBound = $bounds[$i - 1];

            if ($curBound[2] === '+') {
                // If this is a low bound,
                // add the key of the interval to the array of active intervals.
                $activeIntervals[$curBound[3]] = true;
            } else {
                // If this is an high bound, remove the key.
                unset($activeIntervals[$curBound[3]]);
            }

            if (empty($activeIntervals)) {
                // If no intervals are active on this bound,
                // the value of this interval is null.
                $ival = null;
            } else {
                // Else, aggregate the values of the corresponding intervals.
                $ival = array_reduce(
                    array_intersect_key($origIntVals, $activeIntervals),
                    $this->aggregateFunction
                );
            }

            $newIntervals[] = [$curBound[0], $bounds[$i][0], $ival];
        }

        return $newIntervals;
    }

    /**
     * Define the function to convert the interval values to a numeric value
     * in order to match them to a color on the palette.
     *
     * @param \Closure $valueToNumeric
     * @return IntervalGraph
     */
    public function setValueToNumeric(\Closure $valueToNumeric)
    {
        $this->valueToNumeric = $valueToNumeric;
        return $this;
    }

    /**
     * Define the  function to convert the interval values to strings
     * in order to display them in the view.
     *
     * @param \Closure $valueToString
     * @return IntervalGraph
     */
    public function setValueToString(\Closure $valueToString)
    {
        $this->valueToString = $valueToString;
        return $this;
    }

    /**
     * Define the function to aggregate interval values.
     *
     * @param \Closure $aggregate
     * @return IntervalGraph
     */
    public function setAggregate(\Closure $aggregate)
    {
        $this->aggregateFunction = $aggregate;
        return $this;
    }

    /**
     * Set the function to convert interval bound values to string.
     *
     * @param \Closure $boundToString
     * @return IntervalGraph
     */
    public function setBoundToString($boundToString)
    {
        $this->boundToString = $boundToString;
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
     * @param \Closure $boundToNumeric
     * @return IntervalGraph
     */
    public function setBoundToNumeric($boundToNumeric)
    {
        $this->boundToNumeric = $boundToNumeric;
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