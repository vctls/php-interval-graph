<?php

include_once 'Timeline.php';

function rd($min = 1514764800, $max = 1577750400)
{
    return (new DateTime)->setTimestamp(mt_rand($min, $max));
}

// TODO Display the intervals individually before each timeline.

$overlappedIntervals = [
    [new DateTime('now'), new DateTime('now + 4 days'), 7 / 10],
    [new DateTime('now + 1 day'), new DateTime('now + 5 days'), 3 / 10],
    [new DateTime('now + 2 day'), new DateTime('now + 3 days'), 3 / 10],
];
$overlapped = new Timeline($overlappedIntervals);

$withNullIntervals = new Timeline([
    [new DateTime('now'), new DateTime('now + 3 days'), 4 / 10],
    [new DateTime('now + 1 day'), new DateTime('now + 2 days')],
    [new DateTime('now + 2 day'), new DateTime('now + 3 days'), 4 / 10],
    [new DateTime('now + 4 day'), new DateTime('now + 5 days'), 5 / 10],
]);

$longIntervals = [
    [new DateTime('today'), new DateTime('today + 3 days'), 2 / 10],
    [new DateTime('today + 1 day'), new DateTime('today + 4 days'), 2 / 10],
    [new DateTime('today + 2 day'), new DateTime('today + 5 days'), 3 / 10],
    [new DateTime('today + 3 day'), new DateTime('today + 6 days'), 5 / 10],
    [new DateTime('today + 4 day'), new DateTime('today + 7 days'), 4 / 10],
    [new DateTime('today + 5 day'), new DateTime('today + 8 days'), 2 / 10],
    [new DateTime('today + 6 day'), new DateTime('today + 9 days'), 2 / 10],
];

$long = new Timeline($longIntervals, 'Y-m-d H:i:s');
$today = new DateTime('today');
$date1 = (clone $today)->add(new DateInterval('PT60H'));
$date2 = (clone $today)->add(new DateInterval('PT108H'));
$date3 = (clone $date2)->add(new DateInterval('PT60H'));

$truncated = new Timeline(Timeline::truncate(
        $longIntervals,
        $date1,
        $date2
), 'Y-m-d H:i:s');


$withDates = new Timeline([
    [$date1, $date1],
    [new DateTime('now'), new DateTime('now + 4 days'), 7 / 10],
    [$date2, $date2],
    [new DateTime('now + 1 day'), new DateTime('now + 5 days'), 3 / 10],
    [new DateTime('now + 2 day'), new DateTime('now + 3 days'), 3 / 10],
    [$date3, $date3],
]);

$timelines = [];
foreach (range(0, 20) as $t) {
    $intervals = [];
    $j = (int)rand(3, 6);
    for ($i = 0; $i < $j; $i++) {
        $intervals[] = [rd(), rd(), rand(1, 9) / 10];
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
<body style="font-family: sans-serif">
<header>
    <h1>Php Timeline demo</h1>
</header>
<p>
    Php Timeline is a small utility to manipulate and display arrays of weighted date intervals..
</p>
<a href="https://github.com/vctls/php-timeline">https://github.com/vctls/php-timeline</a>
<p>Overlapping intervals with a total rate reaching higher than 100%.</p>
<?= $overlapped ?>

<p>
    Overlapping intervals with a couple null intervals.<br>
    The first null interval overlaps a non null one.
    This cuts the non null interval, while the weight remains the same.<br>
    The second null interval is implicit. It is simply the gap between the two last intervals.
</p>
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
    A timeline with three isolated dates, of which one goes beyond all intervals.
<?= $withDates ?>
</p>
<p>A bunch of random timelines.</p>
<?php foreach ($timelines as $timeline): ?>
    <?= $timeline ?>
<?php endforeach; ?>
<br>
</body>
</html>
