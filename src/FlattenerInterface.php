<?php

namespace Vctls\IntervalGraph;

use Closure;

interface FlattenerInterface
{
    /**
     * Set the Closure for decrementing bound values when dealing with
     * discontinuous sets.
     *
     * @param Closure $substractStep
     * @return FlattenerInterface
     */
    public function setSubstractStep(Closure $substractStep): FlattenerInterface;

    /**
     * Set the Closure for incrementing bound values when dealing with
     * discontinuous sets.
     *
     * @param Closure $addStep
     * @return FlattenerInterface
     */
    public function setAddStep(Closure $addStep): FlattenerInterface;

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
    public static function intervalsToSignedBounds(array $intervals): array;

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
    public static function extractDiscreteValues(array &$intervals): array;

    /**
     * Create each new interval and calculate its value based on the active intervals on each bound.
     *
     * @param array[] $intervals
     * @return array[]
     */
    public function flatten(array $intervals): array;
}
