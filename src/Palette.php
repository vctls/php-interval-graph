<?php
/**
 * User: Victor
 * Date: 2018-04-14
 * Time: 13:31
 */

namespace Vctls\IntervalGraph;

/**
 * Holds color information for the graph.
 *
 * @package Vctls\IntervalGraph
 */
class Palette
{
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
     * Ranges should be simple arrays, containing only the
     * upper bound and the corresponding color value, like this:
     *  [ 50, '#ff9431' ]
     *
     * For discrete values, simply insert the same value twice.
     *
     * @param array $palette
     * @return Palette
     */
    public function setColors(array $palette)
    {
        usort($palette, function ($p1, $p2) {
            return $p2[0] - $p1[0];
        });
        $this->palette = $palette;
        return $this;
    }

    /**
     * @param $color
     * @return $this
     */
    public function setBGColor($color)
    {
        $this->bgColor = $color;
        return $this;
    }

    /**
     * Get the hexadecimal color code for the given percentage.
     * TODO Maybe consider using a segment tree for better performance.
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
            if ($i === 0 && $percent < $palette[$i][0]
                || $i > 0 && $palette[$i][0] === $palette[$i - 1][0] && $percent === $palette[$i][0]
                || $i > 0 && $palette[$i][0] !== $palette[$i - 1][0] && $percent < $palette[$i][0]
                || $i === (count($palette) - 1) && $percent > $palette[$i][0]
            ) {
                return $palette[$i][1];
            }
        }
        throw new \LogicException("The percentage $percent did not match any range in the color palette.");
    }

}