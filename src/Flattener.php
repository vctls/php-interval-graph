<?php

namespace Vctls\IntervalGraph;

use Closure;

/**
 * Transforms an array of overlapping intervals into another array of adjacent intervals.
 *
 * Each new interval holds the keys of the corresponding original intervals.
 *
 * @package Vctls\IntervalGraph
 */
class Flattener
{
    /** @var Closure Substract one step from a bound value. */
    protected $substractStep;

    /** @var Closure Add one step to the bound value. */
    protected $addStep;

    /**
     * Set the Closure for decrementing bound values when dealing with
     * discontinuous sets.
     *
     * @param Closure $substractStep
     * @return Flattener
     */
    public function setSubstractStep(Closure $substractStep): Flattener
    {
        $this->substractStep = $substractStep;
        return $this;
    }

    /**
     * Set the Closure for incrementing bound values when dealing with
     * discontinuous sets.
     *
     * @param Closure $addStep
     * @return Flattener
     */
    public function setAddStep(Closure $addStep): Flattener
    {
        $this->addStep = $addStep;
        return $this;
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
     * @param array[] $intervals
     * @return array[]
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
     * Create each new interval and calculate its value based on the active intervals on each bound.
     *
     * @param array[] $intervals
     * @return array[]
     */
    public function calcAdjacentIntervals(array $intervals): array
    {

        $bounds = self::intervalsToSignedBounds($intervals);
        $newIntervals = [];
        $activeIntervals = [];

        $boundsCount = count($bounds);

        // Create new intervals for each set of two consecutive bounds,
        // and calculate its total value.
        for ($i = 1; $i < $boundsCount; $i++) {

            // Set the current bound.
            [$curBoundValue, $curBoundType, $curBoundIncluded, $curBoundIntervalKey] = $bounds[$i - 1];
            [$nextBoundValue, $nextBoundType, $nextBoundIncluded] = $bounds[$i];

            if ($curBoundType === '+') {
                // If this is a low bound,
                // add the key of the interval to the array of active intervals.
                $activeIntervals[$curBoundIntervalKey] = true;
            } else {
                // If this is an high bound, remove the key.
                unset($activeIntervals[$curBoundIntervalKey]);
            }

            if (
                isset($this->addStep, $this->substractStep) && (
                    ($nextBoundIncluded && $nextBoundType === '+')
                    || (!$nextBoundIncluded && $nextBoundType === '+')
                )
            ) {
                $newHighBound = ($this->substractStep)($nextBoundValue);
            } else {
                $newHighBound = $nextBoundValue;
            }

            if (
                isset($this->addStep, $this->substractStep) && $curBoundType === '-' && $curBoundIncluded
            ) {
                $newLowBound = ($this->addStep)($curBoundValue);
            } else {
                $newLowBound = $curBoundValue;
            }

            $newIntervals[] = [
                $newLowBound,
                $newHighBound,
                $activeIntervals
            ];
        }

        return $newIntervals;
    }
}
