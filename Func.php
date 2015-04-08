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
	public static function getPossibleTypes(PhpParser\Node\Expr\FuncCall $call)
	{
		$buildin = require (__DIR__.'/func_types.php');
		$name = $call->name->parts[0];
		if (isset($buildin[$name])) {
			return $buildin[$name];
		}
		throw new Exception("no function $name", 1);
	}
	public function getReturnType()
	{
		$type = new Type;
		$stmts = [];
		// 主流程有无 return 语句？
		foreach ($this->stmts as $stmt) {
			$stmts[] = $stmt;
			if ($stmt instanceof Return_) {
				// print_r($stmt);
				if ($stmt->expr) {
					$type->addExpr($stmt->expr);
				} else {
					$type->addType('NULL');
				}
				break; // 主流程中的第一个return语句，忽略其他的。
			}
		}
		foreach ($stmts as $stmt) {
			if ($stmt instanceof PhpParser\Node\Stmt\If_) {
				# code...
			}
		}
		return $type;
	}

}
