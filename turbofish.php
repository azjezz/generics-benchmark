<?php

// `::<` is the solution.

class_alias('DateTime', 'X');

define('F', new DateTime());

const T = 2;
const Y = 1;

$a = [new X<F, T>(Y)];

assert($a[0] === false);
assert($a[1] === true);
