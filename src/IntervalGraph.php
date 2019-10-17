<?php

namespace Vctls\IntervalGraph;

use Closure;
use DateTime;
use Exception;
use InvalidArgumentException;
use JsonSerializable;

/**
 * A class to manipulate and display arrays of weighted intervals.
 *
 * @package Vctls\IntervalGraph
 */
class IntervalGraph implements JsonSerializable
{

    /** @var array Initial intervals */
    protected $intervals;

    /** @var array Processed values */
    protected $values;

    /** @var string Path to the template used for rendering. */
    protected $template = 'template.php';

    /** @var Closure Return a numeric value from the inital bound value. */
    protected $boundToNumeric;

    /** @var Closure Return a string from the initial bound value. */
    protected $boundToString;

    /** @var Closure Return a numeric value from an initial interval value. */
    protected $valueToNumeric;

    /** @var Closure Return a string value from an initial interval value. */
    protected $valueToString;

    /** @var Closure Aggregate interval values. */
    protected $aggregateFunction;

    /** @var Palette */
    protected $palette;

    /** @var Flattener */
    private $flattener;

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

        $this->boundToNumeric = static function (DateTime $bound) {
            return $bound->getTimestamp();
        };

        $this->boundToString = static function (DateTime $bound) {
            return $bound->format('Y-m-d');
        };

        $this->valueToNumeric = static function ($v) {
            return $v === null ? null : (int)($v * 100);
        };

        $this->valueToString = static function ($v) {
            return $v === null ? null : ($v * 100 . '%');
        };

        $this->aggregateFunction = static function ($a, $b) {
            if ($a === null && $b === null) {
                return null;
            }
            return round($a + $b, 2);
        };

        $this->flattener = new Flattener();
        $this->palette = new Palette();
    }

    /**
     * @return Flattener
     */
    public function getFlattener(): Flattener
    {
        return $this->flattener;
    }

    /**
     * @param Flattener $flattener
     * @return IntervalGraph
     */
    public function setFlattener(Flattener $flattener): IntervalGraph
    {
        $this->flattener = $flattener;
        return $this;
    }

    /**
     * @return Closure
     */
    public function getAggregateFunction(): Closure
    {
        return $this->aggregateFunction;
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
    public function checkIntervals(): self
    {

        foreach ($this->intervals as $intervalKey => $interval) {

            // Check that the interval is an array.
            if (!is_array($interval)) {
                $t = gettype($interval);
                throw new InvalidArgumentException(
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
                    } catch (Exception $exception) {
                        // FIXME Handle Type errors?
                        throw new PropertyConversionException(
                            "$property[0] of interval $intervalKey cannot be converted to a $expectedType value " .
                            "with the given '$property[1]To$expectedTypeTitle' function. Error : " .
                            $exception->getMessage()
                        );
                    }

                    $actualType = gettype($value);

                    if (!call_user_func("is_$expectedType", $value)) {
                        throw new PropertyConversionException(
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
     * Render an HTML view of the intervalGraph.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $html = $this->draw();
        } catch (Exception $e) {
            $html = 'Error : ' . $e->getMessage();
        }
        return $html;
    }

    /**
     * Render an HTML view of the intervalGraph.
     *
     * @return string
     */
    public function draw(): string
    {
        if (!isset($this->values)) {
            $this->createView();
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        $vs = $this->values;
        ob_start();
        /** @noinspection PhpIncludeInspection */
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
    public function createView(): IntervalGraph
    {
        $flatIntervals = $this->getFlatIntervals();

        // Extract values.
        $originalValues = array_column($flatIntervals, 2);

        $numVals = [];

        // Change bounds to numeric values.
        foreach ($flatIntervals as $interval) {
            $numVals[] = [
                ($this->boundToNumeric)($interval[0]),
                ($this->boundToNumeric)($interval[1]),
            ];
        }

        // Order by low bound.
        uasort($numVals, static function (array $i1, array $i2) {
            return ($i1[0] < $i2[0]) ? -1 : 1;
        });

        // Get the min bound value.
        $min = reset($numVals)[0];

        // Substract min from all bound values.
        foreach ($numVals as $key => $numVal) {
            $numVals[$key] = [
                $numVal[0] - $min,
                $numVal[1] - $min
            ];
        }

        // Order by high bound.
        uasort($numVals, static function (array $i1, array $i2) {
            return ($i1[1] < $i2[1]) ? -1 : 1;
        });

        // Get max timestamp.
        $max = end($numVals)[1];

        // Calculate percentages.
        foreach ($numVals as $i => $numVal) {
            foreach ($numVal as $j => $value) {
                $numVal[$j] = $max === 0 ? 0 : round($value * 100 / $max, 2);
            }
            $numVals[$i] = $numVal;
        }

        // Put values back in, along with the formatted bound.
        // Since we're using associative sorting functions, we know the keys haven't changed.
        $numKeys = array_keys($numVals);
        foreach ($numKeys as $numKey) {

            [$lowBound, $highBound] = $flatIntervals[$numKey];
            [$startPercent, $endPercent] = $numVals[$numKey];

            if ($lowBound === $highBound) {

                $numVals[$numKey] = [
                    $startPercent, // Single value position percentage
                    ($this->boundToString)($lowBound), // Single value string
                ];

            } else {

                $colval = isset($originalValues[$numKey]) ? ($this->valueToNumeric)($originalValues[$numKey]) : null;
                $strval = isset($originalValues[$numKey]) ? ($this->valueToString)($originalValues[$numKey]) : null;

                $numVals[$numKey] = [
                    $startPercent, // Interval start percentage
                    100 - $endPercent, // Interval end percentage from right
                    // Note: for some reason, using 'widht' instead of 'right'
                    // causes the right border to be hidden underneath the next interval.
                    !empty($originalValues) ? $this->palette->getColor($colval) : 50, // Interval color
                    ($this->boundToString)($lowBound), // Interval start string value
                    ($this->boundToString)($highBound), // Interval end string value
                    !empty($originalValues) ? $strval : null,// Interval string value
                ];
            }
        }

        // Put discrete values at the end and reset indices.
        // Reseting indices ensures the processed values are
        // serialized as correctly ordered JSON arrays.
        usort($numVals, static function ($i) {
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
    public function getFlatIntervals(): array
    {
        $discreteValues = self::extractDiscreteValues($this->intervals);
        $signedBounds = self::intervalsToSignedBounds($this->intervals);
        $adjacentIntervals = $this->flattener->calcAdjacentIntervals($signedBounds);

        // Remove empty interval generated when two or more intervals share a common bound.
        $adjacentIntervals = array_values(array_filter($adjacentIntervals, static function ($i) {
            // Use weak comparison in case of object typed bounds.
            return $i[0] != $i[1];
        }));

        // Calculate aggregates after adjacent intervals.
        $agregated = $this->aggregate($adjacentIntervals, $this->intervals);

        // Push discrete values back into the array.
        if (!empty($discreteValues)) {
            array_push($agregated, ...$discreteValues);
        }

        return $agregated;
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
    public static function extractDiscreteValues(array &$intervals): array
    {
        $discreteValues = array_filter($intervals, static function ($interval) {
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
     * ```
     * bound = [
     *   bound value,
     *   bound type,
     *   TODO included,
     *   interval key,
     *   interval value
     * ]
     * ```
     *
     * @param $intervals
     * @return array
     */
    public static function intervalsToSignedBounds($intervals): array
    {
        $bounds = [];
        foreach ($intervals as $key => $interval) {
            // TODO Get included boolean from interval bound.
            $bounds[] = [$interval[1], '-', true, $key, $interval[2] ?? null];
            $bounds[] = [$interval[0], '+', true, $key, $interval[2] ?? null];
        }
        // Order the bounds.
        usort($bounds, static function (array $d1, array $d2) {
            return ($d1[0] < $d2[0]) ? -1 : 1;
        });
        return $bounds;
    }

    /**
     * Compute the numeric values of interval bounds and values.
     *
     * @return array
     */
    public function computeNumericValues(): array
    {
        $intervals = $this->getFlatIntervals();

        // Extract interval values.
        $intervalValues = array_column($intervals, 2);

        // Convert bounds to numeric values.
        foreach ($intervals as $interval) {
            $numericIntervals[] = [
                ($this->boundToNumeric)($interval[0]),
                ($this->boundToNumeric)($interval[1]),
            ];
        }

        // Order by high bound.
        uasort($numericIntervals, static function (array $i1, array $i2) {
            return ($i1[1] < $i2[1]) ? -1 : 1;
        });


        // Put values back in, along with the formatted bound.
        // Since we're using associative sorting functions, we know the keys haven't changed.
        foreach (array_keys($numericIntervals) as $index => $numKey) {

            [$lowNumericBound, $highNumericBound] = $numericIntervals[$index];

            if ($lowNumericBound === $highNumericBound) {

                $numericIntervals[$index] = $lowNumericBound;

            } else {

                $numericIntervals[$index] = [
                    $lowNumericBound,
                    $highNumericBound,
                    $intervalValues[$index] ?: 0
                ];

            }
        }

        // Put discrete values at the end and reset indices.
        // Reseting indices ensures the processed values are
        // serialized as correctly ordered JSON arrays.
        usort($numericIntervals, static function ($i) {
            return !is_array($i) ? 1 : -1;
        });

        return $numericIntervals;
    }

    /**
     * Define the function to convert the interval values to a numeric value
     * in order to match them to a color on the palette.
     *
     * @param Closure $valueToNumeric
     * @return IntervalGraph
     */
    public function setValueToNumeric(Closure $valueToNumeric): IntervalGraph
    {
        $this->valueToNumeric = $valueToNumeric;
        return $this;
    }

    /**
     * Define the  function to convert the interval values to strings
     * in order to display them in the view.
     *
     * @param Closure $valueToString
     * @return IntervalGraph
     */
    public function setValueToString(Closure $valueToString): IntervalGraph
    {
        $this->valueToString = $valueToString;
        return $this;
    }

    /**
     * Define the function to aggregate interval values.
     *
     * @param Closure $aggregate
     * @return IntervalGraph
     */
    public function setAggregate(Closure $aggregate): IntervalGraph
    {
        $this->aggregateFunction = $aggregate;
        return $this;
    }

    /**
     * Set the function to convert interval bound values to string.
     *
     * @param Closure $boundToString
     * @return IntervalGraph
     */
    public function setBoundToString($boundToString): IntervalGraph
    {
        $this->boundToString = $boundToString;
        return $this;
    }

    /**
     * @return array
     */
    public function getIntervals(): array
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
    public function setIntervals(array $intervals): IntervalGraph
    {
        $this->intervals = $intervals;
        $this->values = null;
        return $this;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Set the PHP template to use for rendering.
     *
     * @param string $template
     * @return IntervalGraph
     */
    public function setTemplate($template): IntervalGraph
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return Palette
     */
    public function getPalette(): Palette
    {
        return $this->palette;
    }

    /**
     * Set the Palette object to be used to determine colors.
     *
     * @param Palette $palette
     * @return IntervalGraph
     */
    public function setPalette($palette): IntervalGraph
    {
        $this->palette = $palette;
        return $this;
    }

    /**
     * @param Closure $boundToNumeric
     * @return IntervalGraph
     */
    public function setBoundToNumeric($boundToNumeric): IntervalGraph
    {
        $this->boundToNumeric = $boundToNumeric;
        return $this;
    }

    /**
     * Return the array of values to be serialized by json_encode.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        if (!isset($this->values)) {
            $this->createView();
        }
        return $this->values;
    }

    /**
     * Walk through an array of adjacent intervals, and compute the aggregated value
     * from the values of the corresponding original intervals.
     *
     * @param array $adjacentIntervals
     * @param array $origIntervals
     * @return array
     */
    private function aggregate(array $adjacentIntervals, array $origIntervals): array
    {
        $origIntVals = [];

        // Get the values of the original intervals, including nulls.
        foreach ($origIntervals as $interval) {
            $origIntVals[] = $interval[2] ?? null;
        }

        // If no intervals are active on this bound,
        // the value of this interval is null.
        // Else, aggregate the values of the corresponding intervals.
        foreach ($adjacentIntervals as $key => $adjacentInterval) {
            if (empty($adjacentInterval[2])) {
                $adjacentIntervals[$key][2] = null;
            } else {
                $adjacentIntervals[$key][2] = array_reduce(
                    array_intersect_key($origIntVals, $adjacentInterval[2]),
                    $this->aggregateFunction
                );
            }
        }
        return $adjacentIntervals;
    }
}
