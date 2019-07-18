<?php

use PHPUnit\Framework\TestCase;
use Vctls\IntervalGraph\Palette;


/**
 * Class PaletteTest
 * @package Vctls\IntervalGraph\Test
 */
class PaletteTest extends TestCase
{

    public function testSetColors(): void
    {
        $palette = new Palette();
        // Try setting unordered color references.
        $palette->setColors([
            [0, 'col_0'],
            [0, 'col_0'],
            [50, 'col_1'],
            [100, 'col_2'],
            [100, 'col_3'],
            [100, 'col_4'],
        ]);
        
        $this->assertEquals($palette->getColor(30), 'col_1');
        $this->assertEquals($palette->getColor(80), 'col_2');
    }
    
    public function testSetBGColor(): void
    {
        $palette = new Palette();
        // Try setting unordered color references.
        $palette->setBGColor('background_color_ref');
        
        $this->assertEquals($palette->getColor(), 'background_color_ref');
    }
}