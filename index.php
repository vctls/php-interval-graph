<?php
function __autoload($class_name)
{
    include $class_name . '.php';
}

function rd()
{
    return (new DateTime)->setTimestamp(mt_rand(1514764800, 1577750400));
}

$intervals = [];
$j = (int)rand(3, 6);
for ($i = 0; $i < $j; $i++) {
    $intervals[] = [rd(), rd(), rand(1, 9) / 10];
}

$intervals2 = Timeline::flatten($intervals);

$timeline1 = Timeline::fromIntervals($intervals)->draw();
$timeline2 = Timeline::fromIntervals($intervals2)->draw();


$test = [
    [new DateTime('now'), new DateTime('now + 4 days'), 7/10],
    [new DateTime('now + 1 day'), new DateTime('now + 5 days'), 3/10],
    [new DateTime('now + 2 day'), new DateTime('now + 3 days'), 3/10],
];

$timelineTest = Timeline::fromIntervals(Timeline::flatten($test))->draw();
?>
<html>
<head>
    <style>
        .bar:hover{
            border: thin solid black;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
<?= $timeline1 ?>
<?= $timeline2 ?>
<?= $timelineTest ?>
</body>
</html>
