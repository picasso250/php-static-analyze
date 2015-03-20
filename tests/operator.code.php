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

    $AdditionAssign += 1; // Sum of $a and $b.
    $SubtractionAssign -= 1; //  Difference of $a and $b.
    $MultiplicationAssign *= 1; //   Product of $a and $b.
    $DivisionAssign /= 1; // Quotient of $a and $b.
    $ModulusAssign %= 1; //  Remainder of $a divided by $b.

    $And = $a & $b;
    $Or = $a | $b;
    $Xor = $a ^ $b;
    $Not = ~ $a   ;
    $Shift_left = $a << $b;
    $Shift_right = $a >> $b;

    $Equal = $a == $b;
    $Identical = $a === $b;
    $Not_equal = $a != $b;
    $Not_equal = $a <> $b;
    $Not_identical = $a !== $b;
    $Less_than = $a < $b;
    $Greater_than  = $a > $b;
    $Less_than_or_equal_to = $a <= $b;
    $Greater_than_or_equal_to  = $a >= $b;

    ++$Pre_increment    ;
    $Post_increment++    ;
    --$Pre_decrement    ;
    $Post_decrement--    ;

    $Pre_increment_Assign = ++$a    ;
    $Post_increment_Assign = $a++    ;
    $Pre_decrement_Assign = --$a    ;
    $Post_decrement_Assign = $a--    ;

    // $AndText = $a and $b   ;
    // $OrText = $a or $b    ;
    // $Xor = $a xor $b   ;
    $LogicalNot = ! $a    ;
    $LogicalAnd = $a && $b    ;
    $LogicalOr = $a || $b    ;

    $concatenation = '2' . 3;
    $concatenationAssign .= '2' . 3;

    $c = [3] + [4];
    $d = (new stdClass())->x;
}
