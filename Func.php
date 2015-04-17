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
			$type = new Type();
			$type->extend($buildin[$name]);
			return $type;
		} else {

		}
		throw new Exception("no function $name", 1);
	}
	public function getReturnType()
	{
		$type = new Type;
		return self::subStmts($this->stmts, $type, 0);
	}
	private static function subStmts($stmtsTotal, $type, $level = 0)
	{
		$stmts = [];
		foreach ($stmtsTotal as $stmt) {
			$stmts[] = $stmt;
			if ($stmt instanceof Return_) {
				if ($stmt->expr) {
					$type->addExpr($stmt->expr);
				} else {
					$type->addType('NULL');
				}
				break; // 主流程中的第一个return语句，忽略其他的。
			}
		}
		if ($level === 0 && !($stmt instanceof Return_)) {
			$type->addType('NULL'); // 如果主流程没有 return 语句
		}
		foreach ($stmts as $stmt) {
			if ($stmt instanceof PhpParser\Node\Stmt\If_) {
				self::subStmts($stmt->stmts, $type, $level+1);
				foreach ($stmt->elseifs as $stmt) {
					self::subIf($stmt, $type, $level+1);
				}
				if ($stmt->else) {
					self::subStmts($stmt->else->stmts, $type, $level+1);
				}
			} elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
				self::subStmts($stmt->stmts, $type, $level+1);
			}
		}
		return $type;
	}
	private static function subIf(PhpParser\Node\Stmt\ElseIf_ $stmt, $type, $level)
	{
		self::subStmts($stmt->stmts, $type, $level);
	}

}
