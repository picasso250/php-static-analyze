<?php

class Operator
{
    public static function isIncrOrDecr($class)
    {
        return in_array($class, [
            'PhpParser\Node\Expr\PreInc',
            'PhpParser\Node\Expr\PostInc',
            'PhpParser\Node\Expr\PreDec',
            'PhpParser\Node\Expr\PostDec',
        ]);
    }
    public static function isLogical($class)
    {
        return in_array($class, [
            'PhpParser\Node\Expr\BooleanNot',
            'PhpParser\Node\Expr\BinaryOp\BooleanOr',
            'PhpParser\Node\Expr\BinaryOp\BooleanAnd',
        ]);
    }
    public static function isComparison($class)
    {
        return in_array($class, [
            'PhpParser\Node\Expr\BinaryOp\Equal',
            'PhpParser\Node\Expr\BinaryOp\Identical',
            'PhpParser\Node\Expr\BinaryOp\NotEqual',
            'PhpParser\Node\Expr\BinaryOp\NotIdentical',
            'PhpParser\Node\Expr\BinaryOp\Smaller',
            'PhpParser\Node\Expr\BinaryOp\Greater',
            'PhpParser\Node\Expr\BinaryOp\SmallerOrEqual',
            'PhpParser\Node\Expr\BinaryOp\GreaterOrEqual',
        ]);
    }
    public static function isBitwise($class)
    {
        return in_array($class, [
            'PhpParser\Node\Expr\BinaryOp\BitwiseAnd',
            'PhpParser\Node\Expr\BinaryOp\BitwiseOr',
            'PhpParser\Node\Expr\BinaryOp\BitwiseXor',
            'PhpParser\Node\Expr\BitwiseNot',
            'PhpParser\Node\Expr\BinaryOp\ShiftLeft',
            'PhpParser\Node\Expr\BinaryOp\ShiftRight',
        ]);
    }
    public static function isArithmeticAssign($class)
    {
        return in_array($class, [
            'PhpParser\Node\Expr\AssignOp\Plus',
            'PhpParser\Node\Expr\AssignOp\Minus',
            'PhpParser\Node\Expr\AssignOp\Mul',
            'PhpParser\Node\Expr\AssignOp\Div',
            // fix mod always return int
            'PhpParser\Node\Expr\AssignOp\Mod',
        ]);
    }
    public static function isArithmetic($class)
    {
        return in_array($class, [
            'PhpParser\Node\Expr\UnaryMinus',
            'PhpParser\Node\Expr\BinaryOp\Plus',
            'PhpParser\Node\Expr\BinaryOp\Minus',
            'PhpParser\Node\Expr\BinaryOp\Mul',
            'PhpParser\Node\Expr\BinaryOp\Div',
            // fix mod always return int
            'PhpParser\Node\Expr\BinaryOp\Mod',
        ]);
    }
}
