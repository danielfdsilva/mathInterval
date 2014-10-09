# Math Intervals
This library lets you use mathematical intervals in php. Trust me, it's way cooler than it sounds :)

## Using MathInterval
Before using this library you should understand what intervals are and how to use them.
[MathIsFun](http://www.mathsisfun.com/sets/intervals.html) does an excellent job at expplaining them so thake a look.

### Interval Notation
MathInterval used a different notation to define the intervals. [MathIsFun](http://www.mathsisfun.com/sets/intervals.html) explains that when we intend to exclude the beginning and ending numbers we use ( ).
For example: ```(5, 12] - Do not include include 5 but include 12.```

In MathInterval we always use [ ] but open them to the opposite side. So the same example would look like: ```]5,12] - Do not include include 5 but include 12.``` If you'd want to also exclude 12 you'd write it as: ```]5,12[ - Do not include include 5 and do not include 12.```

> Although MathInterval uses a different notation bot are accepted as part of [ISO 31-11](http://en.wikipedia.org/wiki/ISO_31-11).

**Important note:** For the time being there's no way to define an interval to infinity.