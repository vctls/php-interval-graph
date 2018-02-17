<?php
function __autoload($class_name)
{
    include $class_name . '.php';
}

function rd()
{
    return (new DateTime)->setTimestamp(mt_rand(1514764800, 1577750400));
}

$timelines = [];
foreach (range(0, 20) as $t) {
    $intervals = [];
    $j = (int)rand(3, 6);
    for ($i = 0; $i < $j; $i++) {
        $intervals[] = [rd(), rd(), rand(1, 9) / 10];
    }
    $timelines[] = Timeline::fromIntervals($intervals)->draw();
}

$test = [
    [new DateTime('now'), new DateTime('now + 4 days'), 7 / 10],
    [new DateTime('now + 1 day'), new DateTime('now + 5 days'), 3 / 10],
    [new DateTime('now + 2 day'), new DateTime('now + 3 days'), 3 / 10],
];

$timelineTest = Timeline::fromIntervals(Timeline::flatten($test))->draw();
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
<?php foreach ($timelines as $timeline): ?>
    <?= $timeline ?>
<?php endforeach; ?>
<br>
<?= $timelineTest ?>
</body>
</html>
