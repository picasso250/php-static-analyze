<?php

function foo()
{
    $Negation = -3;
    $Addition = $a + $b; // Sum of $a and $b.
    $Subtraction = $a - $b; //  Difference of $a and $b.
    $Multiplication = $a * $b; //   Product of $a and $b.
    $Division = $a / $b; // Quotient of $a and $b.
    $Modulus = $a % $b; //  Remainder of $a divided by $b.
// $Exponentiation = $a ** $b; //   Result of raising $a to the $b'th power. Introduced in PHP 5.6.
    $b = '2' . 3;
    $c = [3] + [4];
    $d = (new stdClass())->x;
    $e = 3 && 4;
}
