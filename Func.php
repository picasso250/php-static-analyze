<?php

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;

class Func
{
	public static function createFromFunction(Function_ $f)
	{
		$func = new self();
		$func->class = null;
		$func->function = $f;
		$func->stmts = $f->stmts;
		return $func;
	}
	public static function createFromClassMethod(Class_ $c, ClassMethod $m)
	{
		$func = new self();
		$func->class = $c;
		$func->method = $m;
		$func->stmts = $m->stmts;
		return $func;
	}
	public static function getPossibleTypes()
	{
		return ['Scalar_String'];
	}
	public function getAllReturn()
	{
		return array_filter($this->stmts, function($s) {
				return $s instanceof Return_;
			});
	}
	public function getReturnType()
	{
		return 
		$a = 1;
		return 1;
	}

}
