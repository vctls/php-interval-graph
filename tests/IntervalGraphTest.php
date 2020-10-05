<?php

namespace Vctls\IntervalGraph\Test;

use DateTime;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vctls\IntervalGraph\IntervalGraph;
use Vctls\IntervalGraph\Truncator;
use Vctls\IntervalGraph\Util\Date as D;

/**
 * Class IntervalGraphTest
 * @package Vctls\IntervalGraph\Test
 */
class IntervalGraphTest extends TestCase
{

    public function testCreate(): void
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
        self::assertInstanceOf(IntervalGraph::class, $intervalGraph, 'An IntervalGraph could not be created.');
    }

    /**
     * Test calculation of values from simple numeric intervals.
     * TODO Separate calculation of values and visual information.
     */
    public function testSimpleIntegerSumIntervals(): void
    {
        $intervalGraph = $this->getSimpleIntegerSumIntervalGraph();
        $values = $intervalGraph->createView()->checkIntervals()->getValues();

        $expected = [
            [0, 66.67, 'color_1', '0', '1', '1'],
            [33.33, 33.33, 'color_1', '1', '2', '2'],
            [66.67, 0, 'color_1', '2', '3', '1']
        ];

        self::assertEquals($expected, $values, "Generated values don't match the expected result.");
    }

    /**
     * Create an IntervalGraph to aggregate simple integer values.
     *
     * @return IntervalGraph
     */
    public function getSimpleIntegerSumIntervalGraph(): IntervalGraph
    {
        $intervals = [
            [0, 2, 1],
            [1, 3, 1]
        ];

        $intvg = (new IntervalGraph($intervals))
            ->setBoundToNumeric(static function (int $bound) {
                return $bound;
            })
            ->setBoundToString(static function (int $bound) {
                return (string)$bound;
            })
            ->setValueToNumeric(static function (int $value) {
                return $value;
            })
            ->setValueToString(static function (int $value) {
                return (string)$value;
            });
        $intvg->getAggregator()->setAggregateFunction(static function ($a, $b) {return $a + $b;});
        return $intvg;
    }

    /**
     * Provide interval sets to test truncation.
     * @return array
     */
    public function truncationProvider(): array
    {
        $d = static function ($dateString) {
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
    public function testTruncate(array $intervals, array $limits, bool $padding, array $expected): void
    {
        $truncated = Truncator::truncate($intervals, $limits[0], $limits[1], $padding);
        self::assertEquals($expected, $truncated, "Generated values don't match the expected result.");
    }

    public function testFlatIntervals(): void
    {
        $d = static function ($dateString) {
            return DateTime::createFromFormat('Y-m-d h:i:s', $dateString . ' 00:00:00');
        };

        $intervals = [
            [$d('1970-01-01'), $d('1970-01-06')],
            [$d('1970-01-02'), $d('1970-01-04'), 2],
            [$d('1970-01-03'), $d('1970-01-05'), 2],
        ];

        $intervalGraph = D::intvg($intervals);
        $flat = $intervalGraph->getFlattener()->flatten($intervalGraph->getIntervals());
        $flat = $intervalGraph->getAggregator()->aggregate($flat, $intervalGraph->getIntervals());
        /**
         * @var DateTime $lowBound
         * @var DateTime $highBound
         */
        [$lowBound, $highBound] = $flat[2];

        self::assertInstanceOf(DateTime::class, $flat[2][0]);
        self::assertEquals('1970-01-03', $lowBound->format('Y-m-d'));

        self::assertInstanceOf(DateTime::class, $flat[2][1]);
        self::assertEquals('1970-01-04', $highBound->format('Y-m-d'));

        self::assertEquals(4, $flat[2][2]);
    }

    /**
     * Test the `checkIntervals()` method.
     * It MUST throw an InvalidArgumentException if the intervals are incorrect.
     * It MUST NOT throw any exception if the intervals are correct.
     */
    public function testCheck(): void
    {
        $exception = null;
        $badArgument = ['something something'];
        $intervalGraph = new IntervalGraph($badArgument);

        try {
            $intervalGraph->checkIntervals();
        } catch (Exception $exception) {
        }

        self::assertInstanceOf(InvalidArgumentException::class, $exception);

        // TODO Check that bounds and values are compatible with the given conversion closures.
    }

    public function testComputeNumericValues(): void
    {
        $d = static function ($dateString) {
            return DateTime::createFromFormat('Y-m-d h:i:s', $dateString . ' 00:00:00');
        };

        $intervals = [
            [$d('1970-01-01'), $d('1970-01-06')],
            [$d('1970-01-02'), $d('1970-01-04'), 2],
            [$d('1970-01-03'), $d('1970-01-05'), 2],
        ];

        $values = D::intvg($intervals)->computeNumericValues();

        $expected = [
            [0, 86399, 0],
            [86400, 172799, 2],
            [172800, 259200, 4],
            [259201, 345600, 2],
            [345601, 432000, 0]
        ];

        self::assertEquals($expected, $values, "Generated values don't match the expected result.");
    }

    /**
     * Test HTML rendering with the default template.
     */
    public function testToString(): void
    {
        $longIntervals = [
            D::intv(1, 4, 2 / 10),
            D::intv(2, 5, 3 / 10),
            D::intv(3, 6, 5 / 10),
        ];

        $html = "<div class='intvg'>" .
            "<div class='bar bar-intv bar0 color_1 ' style='left:0;right:83.33%' data-title=\"2019-01-02 ➔ 2019-01-03 : 20%\"></div>" .
            "<div class='bar bar-intv bar1 color_2 ' style='left:16.67%;right:66.67%' data-title=\"2019-01-03 ➔ 2019-01-04 : 50%\"></div>" .
            "<div class='bar bar-intv bar2 color_3 ' style='left:33.33%;right:33.33%' data-title=\"2019-01-04 ➔ 2019-01-05 : 100%\"></div>" .
            "<div class='bar bar-intv bar3 color_2 ' style='left:66.67%;right:16.67%' data-title=\"2019-01-05 ➔ 2019-01-06 : 80%\"></div>" .
            "<div class='bar bar-intv bar4 color_2 ' style='left:83.33%;right:0' data-title=\"2019-01-06 ➔ 2019-01-07 : 50%\"></div>" .
            '</div>';

        $intervalGraph = new IntervalGraph($longIntervals);
        self::assertEquals($html, (string)$intervalGraph);
    }

}
