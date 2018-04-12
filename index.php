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

try {
    $date1 = (clone $today)->add(new DateInterval('PT60H'));
    $date2 = (clone $today)->add(new DateInterval('PT108H'));
    $date3 = (clone $date2)->add(new DateInterval('PT60H'));
} catch (Exception $e) {
}

$truncated = new Timeline(Timeline::truncate($longIntervals, $date1, $date2), 'Y-m-d H:i:s');

$withDates = new Timeline([
    [$date1, $date1],
    [$today, new DateTime('today + 4 days'), 7 / 10],
    [$date2, $date2],
    [new DateTime('today + 1 day'), new DateTime('today + 5 days'), 3 / 10],
    [new DateTime('today + 2 day'), new DateTime('today + 3 days'), 3 / 10],
    [$date3, $date3],
]);

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
    <style>
        .bar:hover {
            border: thin solid black;
            box-sizing: border-box;
        }

        /* TODO Prevent the popup from going out of the screen. */
        .bar:hover:after {
            content: attr(data-title);
            padding: 4px 8px;
            color: #333;
            font-family: sans-serif;
            font-size: small;
            background: whitesmoke;
            position: absolute;
            left: 0;
            top: 23px;
            white-space: nowrap;
            z-index: 20;
            border-radius: 5px;
            -moz-box-shadow: 0 0 4px #222;
            -webkit-box-shadow: 0 0 4px #222;
            box-shadow: 0 0 4px #222;
        }
    </style>
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

<p>
    A timeline with lots of intervals.
    <?= $long ?>

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
