<?php

namespace Vctls\IntervalGraph\Test;

use PHPUnit\Framework\TestCase;
use Vctls\IntervalGraph\Flattener;

class FlattenerTest extends TestCase
{
    public function provider(): array
    {
        return [
            [ // First case: ordered, adjacent intervals
                [
                    [0, 1, 5],
                    [2, 3, 5],
                    [4, 5, 10],
                    [6, 8, 10],
                ],
                [
                    [0, 3, 5],
                    [4, 8, 10],
                ]
            ],
            [ // Disordered intervals with no overlap
                [
                    [2, 3, 5],
                    [4, 5, 10],
                    [6, 8, 10],
                    [0, 1, 5],
                ],
                [
                    [0, 3, 5],
                    [4, 8, 10],
                ]
            ],
            [ // Disordered intervals with some overlap
                [
                    [2, 3, 5],
                    [4, 7, 10],
                    [6, 8, 10],
                    [0, 1, 5],
                ],
                [
                    [0, 3, 5],
                    // Overlapping intervals do not change.
                    [4, 7, 10],
                    [6, 8, 10],
                ]
            ],
            [ // Reverse order, no overlap
                [
                    [6, 8, 10],
                    [4, 5, 10],
                    [2, 3, 5],
                    [0, 1, 5],
                ],
                [ // Same as the first one, but the order changes.
                    [4, 8, 10],
                    [0, 3, 5],
                ]
            ],
            [ // Complete overlap
                [
                    [2, 3, 5],
                    [4, 7, 10],
                    [6, 8, 10],
                    [0, 3, 5],
                ],
                [ // No change
                    [2, 3, 5],
                    [4, 7, 10],
                    [6, 8, 10],
                    [0, 3, 5],
                ]
            ],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testJoin($intervals, $expected): void
    {
        $flattener = new Flattener();
        $flattener->setAddStep(function ($value){ return $value + 1;});
        $flattener->setSubstractStep(function ($value){ return $value - 1;});
        // Don't test array keys.
        $actual = array_values($flattener->join($intervals));
        self::assertEquals($actual, $expected);
    }
}
