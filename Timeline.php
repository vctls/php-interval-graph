<?php

/**
 * A Timeline class to manipulate and visualize arrays of dates and weighted date intervals in chronological order.
 */
class Timeline
{
    protected $values;

    private $date_format = "Y-m-d";

    protected function __construct($values)
    {
        $this->values = $values;
    }

    /**
     * Create a timeline from dates.
     *
     * TODO Integrate into fromIntervals
     * @param \DateTime[] $dates
     * @return Timeline
     */
    public static function fromDates(array $dates)
    {
        // Transform the dates in timestamps.
        $timestamps = array_map(function (\DateTime $dateTime) {
            return $dateTime->getTimestamp();
        }, $dates);

        // Sort the array.
        asort($timestamps);

        // Get min and max timestamps.
        $first = $timestamps[0];

        // Substract min to all timestamps.
        $timestamps = array_map(function ($timestamp) use ($first) {
            return $timestamp - $first;
        }, $timestamps);

        $last = end($timestamps);

        // Divide all timestamps by max, and return percentage.
        $timestamps = array_map(function ($timestamp) use ($last) {
            return round($timestamp * 100 / $last);
        }, $timestamps);

        return new self($timestamps);
    }

    /**
     * Create a timeline from weighed intervals.
     * TODO Enforce array format. Should be [ [$startDate, $endDate, $weight] , […]]. Maybe use objects.
     *
     * @param array[] An array of weighted date intervals, with a start date, end date and a weight from 0 to 1
     * @return Timeline
     */
    public static function fromIntervals(array $intervals)
    {
        $intervals = self::flatten($intervals);

        // Extract weights.
        $t = array_column($intervals, 2);

        if (count($t) != 0 && count($t) != count($intervals)) {
            throw new \InvalidArgumentException("Interval weight must be set for all, or no interval.");
        }

        // Change dates to timestamps.
        $values = array_map(function (array $i) {
            return [$i[0]->getTimestamp(), $i[1]->getTimeStamp()];
        }, $intervals);

        // Order by start date.
        uasort($values, function (array $i1, array $i2) {
            return ($i1[0] < $i2[0]) ? -1 : 1;
        });

        // Get the min timestamp.
        $min = reset($values)[0];

        // Substract min from all timestamps.
        $values = array_map(function ($i) use ($min) {
            return [
                $i[0] - $min,
                $i[1] - $min
            ];
        }, $values);

        // Order by end date.
        uasort($values, function (array $i1, array $i2) {
            return ($i1[1] < $i2[1]) ? -1 : 1;
        });

        // Get max timestamp.
        $max = end($values)[1];

        // Calculate percentages.
        $values = array_map(function (array $i) use ($max) {
            return array_map(function ($int) use ($max) {
                return round($int * 100 / $max);
            }, $i);
        }, $values);

        // Put weights back in, along with the formatted date.
        // Since we're using associative sorting functions, we know the keys haven't changed.
        $values = array_map(function ($k, array $i) use ($t, $intervals) {
            return [
                $i[0],
                $i[1],
                !empty($t) ? (int)($t[$k] * 100) : 50,
                $intervals[$k][0],
                $intervals[$k][1]
            ];
        }, array_keys($values), $values);

        return new self($values);
    }

    /**
     * Transform an array of weighted date intervals with possible overlapping
     * into an array of adjacent weighted intervals with no overlapping.
     *
     * @param array $intervals An array of weighted date intervals, with a start date, end date and a weight from 0 to 1
     * @return array
     */
    public static function flatten(array $intervals)
    {

        // Ensure start and end dates are in the right order.
        $intervals = array_map(function (array $i) {
            return $i[0] <= $i[1] ? [$i[0], $i[1], $i[2]] : [$i[1], $i[0], $i[2]];
        }, $intervals);

        // Make an array of dates, assigning the value of the weight for start dates, and ist opposite for end dates.
        foreach ($intervals as $interval) {
            $dates[] = [$interval[0], $interval[2]];
            $dates[] = [$interval[1], -$interval[2]];
        }

        // Order by date.
        usort($dates, function (array $d1, array $d2) {
            return ($d1[0] < $d2[0]) ? -1 : 1;
        });


        $flat = [];
        // Create new intervals for each set of two consecutive dates,
        // and calculate its total weight.
        for ($i = 1; $i < count($dates); $i++) {
            // The weight of the new interval is the sum of the value of each date before its end date.
            $ival = array_reduce(array_column(array_slice($dates, 0, $i), 1),
                function ($a, $b) {
                    // Not using array_sum here because of possible decimal errors.
                    // TODO Find a cleaner method of preventing decimal errors.
                    return round($a + $b, 1);
                });
            $flat[] = [$dates[$i - 1][0], $dates[$i][0], $ival];
        }

        return $flat;
    }

    /**
     * Render an HTML view of the timeline.
     *
     * @return string
     */
    public function draw()
    {
        $vs = $this->values;
        ob_start();
        ?>
        <div class="foo" style="position: relative; background: #f51a00; width: 100%; height: 20px;">
            <?php foreach ($vs as $k => $v) : ?>
                <div class="bar bar<?= $k; ?>" style="
                        height: 20px;
                        background:
                <?php if ($v[2] < 100): ?>
                        rgb(<?= 255 - $v[2] / 2 ?>, <?= $v[2] * 2.5 ?>, 0);
                <?php elseif ($v[2] > 100): ?>
                        rgb(170, 0, <?= $v[2] ?>);
                <?php else: ?>
                        rgb(00, 255, 0);
                <?php endif ?>
                        position: absolute;
                        left:  <?= $v[0] ?>%;
                        right: <?= 100 - $v[1] ?>%;
                        /*width: <?= $v[1] - $v[0] ?>%;*/
                        "
                     data-title="<?=
                     $v[3]->format($this->date_format)
                     . ' ➔ ' . $v[4]->format($this->date_format)
                     . ' : ' . $v[2]
                     ?>%"
                >
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $html = ob_get_contents();
        ob_clean();
        return $html;
    }

    /**
     * Sets the date format used in the data-title attribute of the timeline HTML.
     *
     * @param $format
     */
    public function setDateFormat($format)
    {
        $this->date_format = $format;
    }
}