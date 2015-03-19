<?php

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

class Func
{
	public static function createFromFunction(Function_ $f)
	{
		$func = new self();
		$func->class = null;
		$func->function = $f;
		return $func;
	}
	public static function createFromClassMethod(Class_ $c, ClassMethod $m)
	{
		$func = new self();
		$func->class = $c;
		$func->method = $m;
		return $func;
	}
}
