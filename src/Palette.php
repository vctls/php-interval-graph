<?php

namespace Vctls\IntervalGraph;

use LogicException;

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
     * In order to keep a simple array, the pairs are defined following these rules:
     * Pairs are sorted by value in ascending order.
     * The first pair defines the color for all lower values.
     * The last pair defines the color for all higher values.
     * When two pairs with the same value are consecutive, the second pair defines
     * the color of this discrete value.
     */
    protected $palette = [
        [0, 'color_0'],
        [0, 'color_0'],
        [50, 'color_1'],
        [100, 'color_2'],
        [100, 'color_3'],
        [100, 'color_4'],
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
    public function setColors(array $palette): Palette
    {
        usort($palette, static function ($p1, $p2) {
            return $p2[0] - $p1[0];
        });
        $this->palette = $palette;
        return $this;
    }

    /**
     * @param $color
     * @return $this
     */
    public function setBGColor($color): self
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
    public function getColor($percent): string
    {
        $palette = $this->palette;
        if ($percent === null) {
            return $this->bgColor ?? '';
        }
        foreach ($palette as $i => $iValue) {
            if (($i === 0 && $percent < $iValue[0])
                || ($i > 0 && $iValue[0] === $palette[$i - 1][0] && $percent === $iValue[0])
                || ($i > 0 && $iValue[0] !== $palette[$i - 1][0] && $percent < $iValue[0])
                || ($i === (count($palette) - 1) && $percent > $iValue[0])
            ) {
                return $iValue[1];
            }
        }
        throw new LogicException("The percentage $percent did not match any range in the color palette.");
    }

}