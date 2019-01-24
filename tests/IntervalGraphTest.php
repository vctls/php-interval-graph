<?php

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
        $longIntervals = [
            [new \DateTime('today'), new \DateTime('today + 3 days'), 2 / 10],
            [new \DateTime('today + 1 day'), new \DateTime('today + 4 days'), 2 / 10],
            [new \DateTime('today + 2 day'), new \DateTime('today + 5 days'), 3 / 10],
            [new \DateTime('today + 3 day'), new \DateTime('today + 6 days'), 5 / 10],
            [new \DateTime('today + 4 day'), new \DateTime('today + 7 days'), 4 / 10],
            [new \DateTime('today + 5 day'), new \DateTime('today + 8 days'), 2 / 10],
            [new \DateTime('today + 6 day'), new \DateTime('today + 9 days'), 2 / 10],
        ];

        $intervalGraph = new IntervalGraph($longIntervals);
        $this->assertTrue($intervalGraph instanceof IntervalGraph, 'An IntervalGraph could not be created.');
    }

    /**
     * Test calculation of values from simple numeric intervals.
     * TODO Separate calculation of values and visual information.
     *
     * @throws \Exception
     */
    public function testSimpleIntegerSumIntervals()
    {
        $intervals = [
            [0, 2, 1],
            [1, 3, 1]
        ];

        $intervalGraph = (new IntervalGraph($intervals))
            ->setBoundToNumeric(function (int $bound) {
                return $bound;
            })
            ->setBoundToString(function (int $bound) {
                return (string)$bound;
            })
            ->setValueToNumeric(function (int $value){
                return $value;
            })
            ->setValueToString(function (int $value){
                return (string)$value;
            })
            ->setAggregate(function ($a, $b) {
                return $a + $b;
            })
        ;

        $values = $intervalGraph->process()->checkIntervals()->getValues();

        $expected = [
            [0,67,"#ff9431","0","1","1"],
            [33,33,"#ff9431","1","2","2"],
            [67,0,"#ff9431","2","3","1"]
        ];

        $this->assertEquals($expected, $values, "Generated values don't match the expected result.");

    }

    /**
     * Provide interval sets to test truncation.
     * @return array
     */
    public function truncationProvider()
    {
        $d = function ($dateString){
            return \DateTime::createFromFormat('Y-m-d h:i:s', $dateString . ' 00:00:00');
        };
        
        return [
            [
                [[0, 3, 1], [2, 5, 1]], [1,4],
                [[1, 3, 1], [2, 4, 1]]
            ],
            [
                [[$d('2019-01-10'), $d('2019-02-05'), 5], [$d('2019-01-28'), $d('2019-02-25')], [$d('2019-02-13'), $d('2019-02-16'), 1]],
                [$d('2019-01-15'), $d('2019-02-12')],
                [[$d('2019-01-15'), $d('2019-02-05'), 5], [$d('2019-01-28'), $d('2019-02-12')]]
            ],
            // TODO Borderline cases (same bounds, etc.)
        ];
    }

    /**
     * @dataProvider truncationProvider
     * @param array $intervals
     * @param array $limits
     * @param array $expected
     */
    public function testTruncate(array $intervals, array $limits, array $expected)
    {
        $truncated = IntervalGraph::truncate($intervals, $limits[0], $limits[1]);
        $this->assertEquals($expected, $truncated, "Generated values don't match the expected result.");
    }

    /**
     * @throws \Exception
     */
    public function testFlatIntervals()
    {
        $d = function ($dateString){
            return \DateTime::createFromFormat('Y-m-d h:i:s', $dateString . ' 00:00:00');
        };
        
        $intervals = [
            [$d('1970-01-01'), $d('1970-01-06')],
            [$d('1970-01-02'), $d('1970-01-04'), 2],
            [$d('1970-01-03'), $d('1970-01-05'), 2],
        ];

        $intervalGraph = new IntervalGraph($intervals);
        $flat = $intervalGraph->getFlatIntervals();
        $this->assertTrue($flat[2][0] instanceof \DateTime);
        $this->assertEquals('1970-01-03', $flat[2][0]->format('Y-m-d'));
        $this->assertTrue($flat[2][1] instanceof \DateTime);
        $this->assertEquals('1970-01-04', $flat[2][1]->format('Y-m-d'));
        $this->assertEquals(4, $flat[2][2]);
    }

    /**
     * Test the `checkIntervals()` method.
     * It MUST throw an InvalidArgumentException if the intervals are incorrect.
     * It MUST NOT throw any exception if the intervals are correct.
     */
    public function testCheck()
    {
        // Naive test.
        $this->expectException(\InvalidArgumentException::class);
        $badArgument = ['something something'];
        $intervalGraph = new IntervalGraph($badArgument);
        $intervalGraph->checkIntervals();
        
        // TODO Check that bounds and values are compatible with the given conversion closures.
    }
    
}
