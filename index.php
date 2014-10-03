<?php

require "src/MathRange.php";

$range = new MathRange('[2,5] or [3,6[ or [-9,9]');
print($range);