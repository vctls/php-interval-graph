<?php
function __autoload($class_name)
{
    include $class_name . '.php';
}

function rd($min = 1514764800, $max = 1577750400)
{
    return (new DateTime)->setTimestamp(mt_rand($min, $max));
}

$timelines = [];
foreach (range(0, 20) as $t) {
    $intervals = [];
    $j = (int)rand(3, 6);
    for ($i = 0; $i < $j; $i++) {
        $intervals[] = [rd(), rd(), rand(1, 9) / 10];
    }
    $timelines[] = new Timeline($intervals);
}


$timelineTest = new Timeline([
    [new DateTime('now'), new DateTime('now + 4 days'), 7 / 10],
    [new DateTime('now + 1 day'), new DateTime('now + 5 days'), 3 / 10],
    [new DateTime('now + 2 day'), new DateTime('now + 3 days'), 3 / 10],
]);

$withNullIntervals = new Timeline([
    [new DateTime('now'), new DateTime('now + 3 days'), 4 / 10],
    [new DateTime('now + 1 day'), new DateTime('now + 2 days')],
    [new DateTime('now + 2 day'), new DateTime('now + 3 days'), 4 / 10],
    [new DateTime('now + 4 day'), new DateTime('now + 5 days'), 5 / 10],
]);

$longIntervals = [
    [new DateTime('now'), new DateTime('now + 3 days'), 2 / 10],
    [new DateTime('now + 1 day'), new DateTime('now + 4 days'), 2 / 10],
    [new DateTime('now + 2 day'), new DateTime('now + 5 days'), 3 / 10],
    [new DateTime('now + 3 day'), new DateTime('now + 6 days'), 5 / 10],
    [new DateTime('now + 4 day'), new DateTime('now + 7 days'), 4 / 10],
    [new DateTime('now + 5 day'), new DateTime('now + 8 days'), 2 / 10],
    [new DateTime('now + 6 day'), new DateTime('now + 9 days'), 2 / 10],
];

$long = new Timeline($longIntervals, 'Y-m-d H:i:s');
$lowerBound = new DateTime('now + 60 hours');
$higherBound = new DateTime('now + 108 hours');
$truncated = new Timeline(Timeline::truncate(
        $longIntervals,
        $lowerBound,
        $higherBound
), 'Y-m-d H:i:s');

?>
<html>
<head>
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
<body>
<p>A bunch of random timelines.</p>
<?php foreach ($timelines as $timeline): ?>
    <?= $timeline ?>
<?php endforeach; ?>
<br>
<p>Overlapping intervals with a total rate reaching higher than 100%.</p>
<?= $timelineTest ?>

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
    The same timeline, truncated between <?= $lowerBound->format('Y-m-d H:i:s') ?>
    and <?= $higherBound->format('Y-m-d H:i:s') ?>.
<?= $truncated ?>
</p>
</body>
</html>
