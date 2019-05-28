<?php

/** @noinspection PhpDocMissingThrowsInspection */

require_once 'vendor/autoload.php';

use Vctls\IntervalGraph\IntervalGraph;
use Vctls\IntervalGraph\Util\Date as D;

$today = new DateTime('today');

$base = D::intv(0, 5);
$intv1 = D::intv(0, 4, 7 / 10);
$intv2 = D::intv(1, 5, 3 / 10);
$intv3 = D::intv(2, 3, 3 / 10);
$overlapped1 = D::intvg([$base, $intv1]);
$overlapped2 = D::intvg([$base, $intv2]);
$overlapped3 = D::intvg([$base, $intv3]);
$overlapped = D::intvg([$base, $intv1, $intv2, $intv3]);

$withNull1 = D::intvg([$base, D::intv(0, 3, 4 / 10)]);
$withNull2 = D::intvg([$base, D::intv(1, 2)]);
$withNull3 = D::intvg([$base, D::intv(2, 3, 4 / 10)]);
$withNull4 = D::intvg([$base, D::intv(4, 5, 5 / 10)]);
$withNullIntervals = D::intvg([
    D::intv(0, 3, 4 / 10),
    D::intv(1, 2),
    D::intv(2, 3, 4 / 10),
    D::intv(4, 5, 5 / 10),
]);

$longIntervals = [
    [$today, new DateTime('today + 3 days'), 2 / 10],
    D::intv(1, 4, 2 / 10),
    D::intv(2, 5, 3 / 10),
    D::intv(3, 6, 5 / 10),
    D::intv(4, 7, 4 / 10),
    D::intv(5, 8, 2 / 10),
    D::intv(6, 9, 2 / 10),
];

$longDateFormat = function (DateTime $bound) {
    return $bound->format('Y-m-d H:i:s');
};

$long = (D::intvg($longIntervals))->setBoundToString($longDateFormat);

/*
 * CUSTOM VALUE TYPES
 */

// An aggregate function for arrays representing fractions with the same denominator.
$agg = function ($a, $b) {
    if ($a === null && $b === null) return null;
    return [$a[0] + $b[0], $b[1]];
};

// A toNumeric function…
$toNumeric = function ($a) {
    return $a === null ? null : (int)($a[0] / $a[1] * 100);
};

// A toString function…
$toString = function ($a) {
    return $a === null ? null : ($a[0] . '/' . $a[1]);
};

$fractions = [
    D::intv(1, 4, [2, 10]),
    D::intv(2, 5, [3, 10]),
    D::intv(3, 6, [5, 10]),
    D::intv(4, 7, [4, 10]),
    D::intv(5, 8, [2, 10]),
    D::intv(6, 9, [2, 10]),
];
$fractim = (D::intvg($fractions))
    ->setAggregate($agg)
    ->setValueToNumeric($toNumeric)
    ->setValueToString($toString);
$fract = $fractim->draw();

/* /CUSTOM VALUE TYPES */

/*
 * TRUNCATED INTERVALS
 */
try {
    $intv1 = (clone $today)->add(new DateInterval('PT60H'));
    $intv2 = (clone $today)->add(new DateInterval('PT108H'));
    $intv3 = (clone $intv2)->add(new DateInterval('PT60H'));
} catch (Exception $e) {
}

$truncated = ($fractim->setIntervals(IntervalGraph::truncate($fractions, $intv1, $intv2)))
    ->setBoundToString($longDateFormat);
/* /TRUNCATED INTERVALS */

$withDates = (D::intvg([
    [$intv1, $intv1],
    D::intv(0, 4, 7 / 10),
    [$intv2, $intv2],
    D::intv(1, 5, 3 / 10),
    D::intv(2, 3, 3 / 10),
    [$intv3, $intv3],
]))
    ->setBoundToString($longDateFormat);;

$intvGraphs = [];
foreach (range(0, 20) as $t) {
    $intervals = [];
    $j = (int)rand(3, 6);
    for ($i = 0; $i < $j; $i++) {
        $intervals[] = [D::rdm(), D::rdm(), rand(1, 9) / 10];
    }
    $intvGraphs[] = (D::intvg($intervals))->checkIntervals();
}


?>
<!doctype html>
<html lang="en">
<head>
    <title>Php IntervalGraph demo</title>
    <link rel="stylesheet" href="styles.css">
    <script type="application/javascript" src="app.js"></script>
</head>
<body style="font-family: sans-serif;">
<header>
    <h1>PHP Interval Graph demo</h1>
</header>
<p>
    PHP Interval Graph is a small utility to manipulate and
    display arrays of intervals.
</p>
<a href="https://github.com/vctls/php-interval-graph">
    https://github.com/vctls/php-interval-graph
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
    Gathered on the same graph, they are displayed as follows.
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
    The following graph takes arrays of two values
    and displays them as fractions.<br>
    In order to use custom value types, you need to set the custom functions
    that will aggregate the values, convert them to numeric values and strings.
</p>
<?= $fract ?>

<p>
    The same graph, truncated between <?= $intv1->format('Y-m-d H:i:s') ?>
    and <?= $intv2->format('Y-m-d H:i:s') ?>.
    <?= $truncated ?>
</p>

<p>
    A graph with three isolated dates, shown as black bars.
    <br>One of the dates goes beyond all intervals.
    <?= $withDates ?>
</p>

<?php

/* ADDITIONAL INFORMATION */
$toString2 = function ($a) {
    if ($a === null) {
        return null;
    }
    return $a[0] . '/' . $a[1] . ($a[2] ? '*' : '');
};

$agg2 = function ($a, $b) {
    if ($a === null && $b === null) return null;
    return [
        $a[0] + $b[0],
        $b[1],
        $a[2] || $b[2]
    ];
};

$addInfo = D::intvg([
    D::intv(0, 3),
    D::intv(0, 2, [1, 3, false]),
    D::intv(1, 3, [2, 5, true]),
])
    ->setValueToString($toString2)
    ->setValueToNumeric($toNumeric)
    ->setAggregate($agg2);
?>

<h2>Passing additional information</h2>
<p>
    You can take advantage of the closures and templates to display additional information on the graph.
    Here for example, interval values hold a boolean. Depending on the boolean, an asterisk is added to the string,
    and a class is set on the corresponding bars.
    <?= $addInfo ?>
</p>

<p>
    A bunch of random graphs, this time generated through JavaScript:
    <br>
</p>
<div id="random"></div>
<script>
    'use strict';
    const graphs = JSON.parse('<?= json_encode($intvGraphs) ?>');
    const el = document.getElementById('random');

    try {
        graphs.forEach(function (graph) {
            let html = document.createRange().createContextualFragment(intvg(graph));
            el.appendChild(html);
        });
    } catch (e) {
        el.innerHTML = 'The JavaScript function uses ES6 string literals. Sorry not sorry, IE.';
    }
</script>
</body>
</html>