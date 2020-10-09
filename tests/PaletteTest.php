<?php

use PHPUnit\Framework\TestCase;
use Vctls\IntervalGraph\ClassPalette;


/**
 * Class PaletteTest
 * @package Vctls\IntervalGraph\Test
 */
class PaletteTest extends TestCase
{

    public function testSetColors(): void
    {
        $palette = new ClassPalette();
        // Try setting unordered color references.
        $palette->setColors([
            [0, 'col_0'],
            [0, 'col_0'],
            [50, 'col_1'],
            [100, 'col_2'],
            [100, 'col_3'],
            [100, 'col_4'],
        ]);

        self::assertEquals('col_1', $palette->getColor(30));
        self::assertEquals('col_2', $palette->getColor(80));
    }

    public function testSetBGColor(): void
    {
        $palette = new ClassPalette();
        // Try setting unordered color references.
        $palette->setBGColor('background_color_ref');

        self::assertEquals('background_color_ref', $palette->getColor());
    }
}
