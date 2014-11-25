# Math Intervals
[![Build Status](https://travis-ci.org/danielfdsilva/mathInterval.svg?branch=master)](https://travis-ci.org/danielfdsilva/mathInterval)  

This library lets you use mathematical intervals in php. Trust me, it's way cooler than it sounds :)

Before using this library you should understand what intervals are and how to use them.
[MathIsFun](http://www.mathsisfun.com/sets/intervals.html) does an excellent job at explaining them so take a look.

### Interval Notation
**MathInterval** uses square brackets to define an interval. The direction of each bracket indicates whether we want to include or exclude the beginning and ending number. For example:

```]5,12] - From 5 (excluded) to 12 (included)```  
and  
```]5,12[ - From 5 (excluded) to 12 (excluded)```

This notation is accepted as part of  [ISO 31-11](http://en.wikipedia.org/wiki/ISO_31-11).

**Important note:** For the time being there's no way to define an interval to infinity.

## Using MathInterval

To create a MathInterval instance simply use:
```php
$interval = new MathInterval('[5,20]');
```
With this you create an interval that comprehends all numbers from 5 to 20 (including 5 and 20).  
Now you can start validating number against this interval:
```php
$interval->inInterval(5); // TRUE
$interval->inInterval(10); // TRUE
$interval->inInterval(150); // FALSE

$interval->inInterval(15.25); // FALSE
```
What?! How come 15.25 is ```FALSE```. Isn't it between 5 and 20?  

To simplify validation, MathInterval assumes that only integer values are valid, but don't despair. If you need to allow float numbers you just need to initialize the interval with one of the values as float:

```php
// Initializing one of the values as float, makes the interval handle them.
$interval = new MathInterval('[5.0,20]');

$interval->inInterval(15.25); // TRUE
```

### Union

```php
// Create your first interval.
$interval = new MathInterval('[5,20]');

// Unite with another interval.
$interval->union('[17,33[');

// The resulting interval will be, of course, the union of both.
print $interval; // [5,33[
```

### Intersection
The intersection of intervals works in the same way as union, but with a very different outcome.
```php
// Create your first interval.
$interval = new MathInterval('[5,20]');

// Intersect with another interval.
$interval->intersection('[17,33[');

// The resulting interval will be, of course, the intersection of both.
print $interval; // [17,20]
```

### Expressions
MathInterval also allows you to use expressions to initialize and compute intervals. The official symbols for intersections and union are ∩ and ⋃, but, since these are hard to type, MathInterval uses ```and``` and ```or```.

For example:


```php
// [5,20] ⋃ [10,25]
$interval = new MathIntervalCollection('[5,20] or [10,25]');
print $interval; // [5,25]

// [5,20] ∩ [10,25]
$interval = new MathIntervalCollection('[5,20] and [10,25]');
print $interval; // [10,20]

// You can chain as many values as needed in an expression:
// [5,20] ∩ [10,25] ⋃ [15,30[
$interval = new MathIntervalCollection('[5,20] and [10,25] or [15,30[');
print $interval; // [10,30[

// You can also use these expressions in the union() and intersection() methods.
```
Just like in any mathematical expression, the order of the operators matters and parenthesis can be used to specify the order of the operations.  
For example: ```[5,20] ∩ ([10,25] ⋃ [15,30[)``` = ```[5,20] ∩ [10,30[``` = ```[10,20]```

**With complex expressions you must use `MathIntervalCollection`. All the methods available in `MathInterval` like union and intersection are also available here.**

```php
$interval = new MathIntervalCollection('[1,10]');
$interval->union('[16,20]');
// Note that some intervals are not possible to unite therefore they
// will be left as an expression.
print $interval; // [1,10] or [16,20]


$interval = new MathIntervalCollection('[1,10] or [16,20]');
$interval->intersection(']7,9] or [15,18]');
print $interval; // ]7,9] or [16,18]
// This would be the same as ([1,10] or [16,20]) and (]7,9] or [15,18])
```

-----

## Contribution
You are free to contribute to the project. If you find a bug or have a nice idea about a feature, please open an issue or submit your own solution. I'll be more than happy to hear your suggestions. :)

## Testing
The framework testing is done using phpunit. To run the tests you just need to run ```phpunit``` in the root folder.

##License
MathInterval is licensed under **The MIT License (MIT)**, see the LICENSE file for more details.
