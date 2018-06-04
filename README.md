# PHP Interval Graph [![Build Status](https://travis-ci.org/vctls/php-interval-graph.svg?branch=master)](https://travis-ci.org/vctls/php-interval-graph)

A small utility to manipulate and display arrays of weighted intervals.

It transforms an array of disjointed and overlapping weighted intervals
into an array of adjacent intervals with their total calculated weight.

It was initially made for date intervals carrying simple numeric values,
but you can pass your own closures to make it support anything you want.

It can be used to display availability rates and such over a period of
time in a basic, compatible way.

## Basic usage
1. Create an array of intervals. The first value should be the low bound,
the second value should be the high bound, and the third value should be
the value of the interval, or 'weight'.

2. Create a new IntervalGraph object from the intervals.

3. Print it! You can call the `draw()` method explicitely, or just echo it.
The `__toString()` method will also call `draw()`.

```php
<?php
use Vctls\IntervalGraph\IntervalGraph;

$intervals = [
    [new \DateTime(2018-01-01), new \DateTime(2018-01-04), 0.3],
    [new \DateTime(2018-01-02), new \DateTime(2018-01-05), 0.5],
    [new \DateTime(2018-01-03), new \DateTime(2018-01-06), 0.2],
];

$intervalGraph = new IntervalGraph($intervals);

echo $intervalGraph;
```

Check index.php and the demo for more examples.

Demo : https://php-interval-graph-demo.herokuapp.com/