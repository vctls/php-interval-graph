<?php
/**
 * User: Victor
 * Date: 2018-04-06
 * Time: 19:27
 */

namespace Vctls\IntervalGraph\Test;

use Vctls\IntervalGraph\IntervalGraph;

/**
 * Class IntervalGraphTest
 * @package Vctls\IntervalGraph\Test
 */
class IntervalGraphTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \Exception
     */
    public function testCreate()
    {
        $intervals = [
            [new \DateTime('today'), new \DateTime('today + 5 days')],
            [new \DateTime('today + 1 day'), new \DateTime('today + 3 days'), 2 / 10],
            [new \DateTime('today + 2 day'), new \DateTime('today + 4 days'), 3 / 10],
        ];

        $intervalGraph = new IntervalGraph($intervals);
        $this->assertTrue($intervalGraph instanceof IntervalGraph, 'A intervalGraph could not be created.');
    }
    /**
     * @throws \Exception
     */
    public function testFlatIntervals()
    {
        $intervals = [
            [new \DateTime('1970-01-01'), new \DateTime('1970-01-06')],
            [new \DateTime('1970-01-02'), new \DateTime('1970-01-04'), 2],
            [new \DateTime('1970-01-03'), new \DateTime('1970-01-05'), 2],
        ];

        $intervalGraph = new IntervalGraph($intervals);
        $flat = $intervalGraph->getFlatIntervals();
        $this->assertTrue($flat[2][0] instanceof \DateTime);
        $this->assertEquals('1970-01-03', $flat[2][0]->format('Y-m-d'));
        $this->assertTrue($flat[2][1] instanceof \DateTime);
        $this->assertEquals('1970-01-04', $flat[2][1]->format('Y-m-d'));
        $this->assertEquals(4, $flat[2][2]);
    }

    public function testCheck()
    {
        $this->expectException(\InvalidArgumentException::class);
        $badArgument = ['something something'];
        IntervalGraph::checkFormat($badArgument);
    }

    // TODO Do some actual tests.
}
