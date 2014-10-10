# Math Intervals
This library lets you use mathematical intervals in php. Trust me, it's way cooler than it sounds :)

Before using this library you should understand what intervals are and how to use them.
[MathIsFun](http://www.mathsisfun.com/sets/intervals.html) does an excellent job at expplaining them so thake a look.

### Interval Notation
MathInterval uses a different notation to define the intervals. [MathIsFun](http://www.mathsisfun.com/sets/intervals.html) explains that when we intend to exclude the beginning and ending numbers we use ( ).
For example: ```(5, 12] - Do not include include 5 but include 12.```

In MathInterval we always use [ ] but open them to the opposite side. So the same example would look like: ```]5,12] - Do not include include 5 but include 12.``` If you'd want to also exclude 12 you'd write it as: ```]5,12[ - Do not include include 5 and do not include 12.```

> Although MathInterval uses a different notation bot are accepted as part of [ISO 31-11](http://en.wikipedia.org/wiki/ISO_31-11).

**Important note:** For the time being there's no way to define an interval to infinity.

## Using MathInterval

To create a MathInterval instance simply use:
```php
$interval = new MathInterval('[5,20]');
```
With this you just created an interval that compreends all numbers from 5 to 20 (including 5 and 20).
Now you can start validating number against this interval:
```php
$interval->inInterval(5); // TRUE
$interval->inInterval(10); // TRUE
$interval->inInterval(150); // FALSE

$interval->inInterval(15.25); // FALSE
```
What?! How come 15.25 is ```FALSE```. Isn't it between 5 and 20?  
To simplify validation, MathInterval assumes that only integer values are valid but don't dispair. If you need to allow float numbers you just need to initialize the interval with one of the values as float:
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
The intersection of intervals works in the same way as union but with a very different outcome.
```php
// Create your first interval.
$interval = new MathInterval('[5,20]');

// Intersect with another interval.
$interval->intersection('[17,33[');

// The resulting interval will be, of course, the intersection of both.
print $interval; // [17,20]
```

-----

## Contribution
You are free to contribute to the project. If you find a bug and/or have a nice idea about a feature feel free to open an issue or submit your own solution. I'll be more than happy to hear your suggestions. :)

## Testing
The framework testing is done using phpunit. To run the tests you just need to run ```phpunit``` in the root folder.

##License
MathInterval is licensed under **The MIT License (MIT)**, see the LICENSE file for more details.