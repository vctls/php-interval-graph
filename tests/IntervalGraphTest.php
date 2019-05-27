<?php

namespace Vctls\IntervalGraph\Test;

use DateTime;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vctls\IntervalGraph\IntervalGraph;
use Vctls\IntervalGraph\Util\Date as D;

/**
 * Class IntervalGraphTest
 * @package Vctls\IntervalGraph\Test
 */
class IntervalGraphTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCreate()
    {
        $longIntervals = [
            [new DateTime('today'), new DateTime('today + 3 days'), 2 / 10],
            D::intv(1, 4, 2 / 10),
            D::intv(2, 5, 3 / 10),
            D::intv(3, 6, 5 / 10),
            D::intv(4, 7, 4 / 10),
            D::intv(5, 8, 2 / 10),
            D::intv(6, 9, 2 / 10),
        ];

        $intervalGraph = new IntervalGraph($longIntervals);
        $this->assertTrue($intervalGraph instanceof IntervalGraph, 'An IntervalGraph could not be created.');
    }

    /**
     * Test calculation of values from simple numeric intervals.
     * TODO Separate calculation of values and visual information.
     *
     * @throws Exception
     */
    public function testSimpleIntegerSumIntervals()
    {
        $intervalGraph = $this->getSimpleIntegerSumIntervalGraph();
        $values = $intervalGraph->createView()->checkIntervals()->getValues();

        $expected = [
            [0, 67, "color_1", "0", "1", "1"],
            [33, 33, "color_1", "1", "2", "2"],
            [67, 0, "color_1", "2", "3", "1"]
        ];

        $this->assertEquals($expected, $values, "Generated values don't match the expected result.");
    }

    /**
     * Create an IntervalGraph to aggregate simple integer values. 
     * 
     * @return IntervalGraph
     */
    public function getSimpleIntegerSumIntervalGraph()
    {
        $intervals = [
            [0, 2, 1],
            [1, 3, 1]
        ];

        return (new IntervalGraph($intervals))
            ->setBoundToNumeric(function (int $bound) {
                return $bound;
            })
            ->setBoundToString(function (int $bound) {
                return (string)$bound;
            })
            ->setValueToNumeric(function (int $value) {
                return $value;
            })
            ->setValueToString(function (int $value) {
                return (string)$value;
            })
            ->setAggregate(function ($a, $b) {
                return $a + $b;
            });

    }

    /**
     * Provide interval sets to test truncation.
     * @return array
     */
    public function truncationProvider()
    {
        $d = function ($dateString) {
            return DateTime::createFromFormat('Y-m-d h:i:s', $dateString . ' 00:00:00');
        };

        return [
            [
                [[0, 3, 1], [2, 5, 1]], [1, 4], false,
                [[1, 3, 1], [2, 4, 1]]
            ],
            [
                [[0, 3, 1], [2, 5, 1]], [-1, 6], true,
                [[-1, 0], [0, 3, 1], [2, 5, 1], [5, 6]]
            ],
            [
                [[$d('2019-01-10'), $d('2019-02-05'), 5], [$d('2019-01-28'), $d('2019-02-25')], [$d('2019-02-13'), $d('2019-02-16'), 1]],
                [$d('2019-01-15'), $d('2019-02-12')], false,
                [[$d('2019-01-15'), $d('2019-02-05'), 5], [$d('2019-01-28'), $d('2019-02-12')]]
            ],
            // TODO Borderline cases (same bounds, etc.)
        ];
    }

    /**
     * @dataProvider truncationProvider
     * @param array $intervals
     * @param array $limits
     * @param bool $padding
     * @param array $expected
     */
    public function testTruncate(array $intervals, array $limits, bool $padding, array $expected)
    {
        $truncated = IntervalGraph::truncate($intervals, $limits[0], $limits[1], $padding);
        $this->assertEquals($expected, $truncated, "Generated values don't match the expected result.");
    }

    /**
     * @throws Exception
     */
    public function testFlatIntervals()
    {
        $d = function ($dateString) {
            return DateTime::createFromFormat('Y-m-d h:i:s', $dateString . ' 00:00:00');
        };

        $intervals = [
            [$d('1970-01-01'), $d('1970-01-06')],
            [$d('1970-01-02'), $d('1970-01-04'), 2],
            [$d('1970-01-03'), $d('1970-01-05'), 2],
        ];

        $intervalGraph = D::intvg($intervals);
        $flat = $intervalGraph->getFlatIntervals();
        /**
         * @var DateTime $lowBound
         * @var DateTime $highBound
         */
        list($lowBound, $highBound) = $flat[2];
        
        $this->assertTrue($flat[2][0] instanceof DateTime);
        $this->assertEquals('1970-01-03', $lowBound->format('Y-m-d'));
        
        $this->assertTrue($flat[2][1] instanceof DateTime);
        $this->assertEquals('1970-01-04', $highBound->format('Y-m-d'));
        
        $this->assertEquals(4, $flat[2][2]);
    }

    /**
     * Test the `checkIntervals()` method.
     * It MUST throw an InvalidArgumentException if the intervals are incorrect.
     * It MUST NOT throw any exception if the intervals are correct.
     */
    public function testCheck()
    {
        $exception = null;
        $badArgument = ['something something'];
        $intervalGraph = new IntervalGraph($badArgument);
        
        try {
            $intervalGraph->checkIntervals();
        } catch (Exception $exception) {
        }
        
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);

        // TODO Check that bounds and values are compatible with the given conversion closures.
    }
    
    public function testComputeNumericValues()
    {
        $d = function ($dateString) {
            return DateTime::createFromFormat('Y-m-d h:i:s', $dateString . ' 00:00:00');
        };

        $intervals = [
            [$d('1970-01-01'), $d('1970-01-06')],
            [$d('1970-01-02'), $d('1970-01-04'), 2],
            [$d('1970-01-03'), $d('1970-01-05'), 2],
        ];
        
        $values = (D::intvg($intervals))->computeNumericValues();
        
        $expected = [
            [0, 86399, 0],
            [86400, 172799, 2],
            [172800, 259200, 4],
            [259201, 345600, 2],
            [345601, 432000, 0]
        ];
        
        $this->assertEquals($expected, $values, "Generated values don't match the expected result.");
    }
    
}
