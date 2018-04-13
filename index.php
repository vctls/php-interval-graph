<?php

require_once 'vendor/autoload.php';

/**
 * Generate random dates.
 * @param int $min
 * @param int $max
 * @return DateTime
 */
function rdm($min = 1514764800, $max = 1577750400)
{
    return (new DateTime)->setTimestamp(mt_rand($min, $max));
}

$today = new DateTime('today');

$base = [$today, new DateTime('today + 5 days')];
$date1 = [$today, new DateTime('today + 4 days'), 7 / 10];
$date2 = [new DateTime('today + 1 day'), new DateTime('today + 5 days'), 3 / 10];
$date3 = [new DateTime('today + 2 day'), new DateTime('today + 3 days'), 3 / 10];
$overlapped1 = new Timeline([$base, $date1]);
$overlapped2 = new Timeline([$base, $date2]);
$overlapped3 = new Timeline([$base, $date3]);
$overlapped = new Timeline([$base, $date1, $date2, $date3]);

$withNull1 = new Timeline([$base, [new DateTime('today'), new DateTime('today + 3 days'), 4 / 10],]);
$withNull2 = new Timeline([$base, [new DateTime('today + 1 day'), new DateTime('today + 2 days')],]);
$withNull3 = new Timeline([$base, [new DateTime('today + 2 day'), new DateTime('today + 3 days'), 4 / 10],]);
$withNull4 = new Timeline([$base, [new DateTime('today + 4 day'), new DateTime('today + 5 days'), 5 / 10],]);
$withNullIntervals = new Timeline([
    [$today, new DateTime('today + 3 days'), 4 / 10],
    [new DateTime('today + 1 day'), new DateTime('today + 2 days')],
    [new DateTime('today + 2 day'), new DateTime('today + 3 days'), 4 / 10],
    [new DateTime('today + 4 day'), new DateTime('today + 5 days'), 5 / 10],
]);

$longIntervals = [
    [$today, new DateTime('today + 3 days'), 2 / 10],
    [new DateTime('today + 1 day'), new DateTime('today + 4 days'), 2 / 10],
    [new DateTime('today + 2 day'), new DateTime('today + 5 days'), 3 / 10],
    [new DateTime('today + 3 day'), new DateTime('today + 6 days'), 5 / 10],
    [new DateTime('today + 4 day'), new DateTime('today + 7 days'), 4 / 10],
    [new DateTime('today + 5 day'), new DateTime('today + 8 days'), 2 / 10],
    [new DateTime('today + 6 day'), new DateTime('today + 9 days'), 2 / 10],
];

$long = new Timeline($longIntervals, 'Y-m-d H:i:s');

/*
 * CUSTOM VALUE TYPES
 */

// An aggregate function for arrays representing fractions with the same denominator.
$agg = function ($a, $b) {
    if ($a === null && $b === null) return null;
    return [$a[0] + $b[0], $b[1]];
};

// A toNumeric function…
$toNumeric = function ($a) {return $a === null ? null : (int)($a[0] / $a[1] * 100);};

// A toString function…
$toString = function ($a) {return $a === null ? null : ($a[0] . '/' . $a[1]);};

$fractions = [
    [$today, new DateTime('today + 3 days'), [2, 10]],
    [new DateTime('today + 1 day'), new DateTime('today + 4 days'), [2, 10]],
    [new DateTime('today + 2 day'), new DateTime('today + 5 days'), [3, 10]],
    [new DateTime('today + 3 day'), new DateTime('today + 6 days'), [5, 10]],
    [new DateTime('today + 4 day'), new DateTime('today + 7 days'), [4, 10]],
    [new DateTime('today + 5 day'), new DateTime('today + 8 days'), [2, 10]],
    [new DateTime('today + 6 day'), new DateTime('today + 9 days'), [2, 10]],
];
$fractim = (new Timeline($fractions))->setAggregateFunction($agg)
    ->setValueToNumericFunction($toNumeric)
    ->setValueToStringFunction($toString);
$fract = $fractim->draw();

/* /CUSTOM VALUE TYPES */

/*
 * TRUNCATED INTERVALS
 */
try {
    $date1 = (clone $today)->add(new DateInterval('PT60H'));
    $date2 = (clone $today)->add(new DateInterval('PT108H'));
    $date3 = (clone $date2)->add(new DateInterval('PT60H'));
} catch (Exception $e) {
}

$truncated = $fractim
    ->setIntervals(Timeline::truncate($fractions, $date1, $date2))
    ->setDateFormat( 'Y-m-d H:i:s');

$withDates = new Timeline([
    [$date1, $date1],
    [$today, new DateTime('today + 4 days'), 7 / 10],
    [$date2, $date2],
    [new DateTime('today + 1 day'), new DateTime('today + 5 days'), 3 / 10],
    [new DateTime('today + 2 day'), new DateTime('today + 3 days'), 3 / 10],
    [$date3, $date3],
]);
/* /TRUNCATED INTERVALS */

$timelines = [];
foreach (range(0, 20) as $t) {
    $intervals = [];
    $j = (int)rand(3, 6);
    for ($i = 0; $i < $j; $i++) {
        $intervals[] = [rdm(), rdm(), rand(1, 9) / 10];
    }
    $timelines[] = new Timeline($intervals);
}

?>
<html>
<head>
    <title>Php Timeline demo</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body style="font-family: sans-serif;">
<header>
    <h1>Php Timeline demo</h1>
</header>
<p>
    Php Timeline is a small utility to manipulate and
    display arrays of weighted date intervals..
</p>
<a href="https://github.com/vctls/php-timeline">
    https://github.com/vctls/php-timeline
</a>
<h2>How it works</h2>
<p>
    Here are three overlapping date intervals.
    Each one has a linked rate, displayed as a percentage when hovering it.
    <br>They are all displayed over the same period of time, which has no rate.
</p>
<div style="margin-bottom: 2px"><?= $overlapped1 ?></div>
<div style="margin-bottom: 2px"><?= $overlapped2 ?></div>
<div style="margin-bottom: 2px"><?= $overlapped3 ?></div>

<p>
    Gathered on the same timeline, they are displayed as follows.
</p>
<?= $overlapped ?>

<h2>More examples</h2>
<p>
    Overlapping intervals with a couple null intervals.<br>
    The first null interval overlaps a non null one.
    This cuts the non null interval, while the weight remains the same.<br>
    The second null interval is implicit.
    It is simply the gap between the two last intervals.
</p>
<div style="margin-bottom: 2px"><?= $withNull1 ?></div>
<div style="margin-bottom: 2px"><?= $withNull2 ?></div>
<div style="margin-bottom: 2px"><?= $withNull3 ?></div>
<div style="margin-bottom: 2px"><?= $withNull4 ?></div>
<br>
<?= $withNullIntervals ?>


<h2>Custom value types</h2>
<p>
    The following timeline takes arrays of two values
    and displays them as fractions.<br>
    In order to use custom value types, you need to set the custom functions
    that will aggregate the values, convert them to numeric values and strings.
</p>
<?= $fract ?>

<p>
    The same timeline, truncated between <?= $date1->format('Y-m-d H:i:s') ?>
    and <?= $date2->format('Y-m-d H:i:s') ?>.
    <?= $truncated ?>
</p>

<p>
    A timeline with three isolated dates, shown as black bars.
    <br>One of the dates goes beyond all intervals.
    <?= $withDates ?>
</p>
<p>A bunch of random timelines.</p>
<?php foreach ($timelines as $timeline): ?>
    <?= $timeline ?>
<?php endforeach; ?>
<br>
</body>
</html>
