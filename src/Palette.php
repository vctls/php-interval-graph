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
     * Make sure values are in the correct order.
     *
     * @param array $palette
     * @return Palette
     */
    public function setColors(array $palette): Palette
    {
        $this->palette = $palette;
        return $this;
    }

    /**
     * @param string $color A color reference or hex code.
     * @return $this
     */
    public function setBGColor(string $color): self
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
    public function getColor(int $percent = null): string
    {
        $pal = $this->palette;
        if ($percent === null) {
            return $this->bgColor ?? '';
        }
        foreach ($pal as $i => $iValue) {
            if (($i === 0 && $percent < $iValue[0])
                || ($i > 0 && $iValue[0] === $pal[$i - 1][0] && $percent === $iValue[0])
                || ($i > 0 && $iValue[0] !== $pal[$i - 1][0] && $percent < $iValue[0])
                || ($i === (count($pal) - 1) && $percent > $iValue[0])
            ) {
                return $iValue[1];
            }
        }
        throw new LogicException("The percentage $percent did not match any range in the color palette.");
    }

}