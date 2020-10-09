<?php

namespace Vctls\IntervalGraph;

/**
 * Holds color information for the graph.
 *
 * @package Vctls\IntervalGraph
 */
interface PaletteInterface
{
    /**
     * Get the color information for the given percentage.
     *
     * @param int|null $percent
     * @return string
     */
    public function getColor(int $percent = null): string;
}
