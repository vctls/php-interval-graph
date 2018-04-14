<?php
/**
 * User: Victor
 * Date: 2018-04-06
 * Time: 19:27
 */

class IntervalGraphTest extends \PHPUnit\Framework\TestCase
{
    public function testDraw()
    {
        $longIntervals = [
            [new DateTime('today'), new DateTime('today + 3 days'), 2 / 10],
            [new DateTime('today + 1 day'), new DateTime('today + 4 days'), 2 / 10],
            [new DateTime('today + 2 day'), new DateTime('today + 5 days'), 3 / 10],
            [new DateTime('today + 3 day'), new DateTime('today + 6 days'), 5 / 10],
            [new DateTime('today + 4 day'), new DateTime('today + 7 days'), 4 / 10],
            [new DateTime('today + 5 day'), new DateTime('today + 8 days'), 2 / 10],
            [new DateTime('today + 6 day'), new DateTime('today + 9 days'), 2 / 10],
        ];

        $long = new IntervalGraph($longIntervals, 'Y-m-d H:i:s');
        $this->assertTrue($long instanceof IntervalGraph, 'A intervalGraph could not be created.');
    }

    public function testCheck()
    {
        $this->expectException(\InvalidArgumentException::class);
        $badArgument = ['something something'];
        IntervalGraph::checkFormat($badArgument);
    }

    // TODO Do some actual tests.
}