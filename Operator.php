<?php

class Operator
{
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
