<?php

class Value
{
	public $types = [];
	// public $isAny = true;
	// public $values = [];

	public function __construct($type = null)
	{
		if ($type) {
			$this->types[] = $type;
		}
	}
	public static $map = [
		'scalar' => ['Scalar_LNumber', 'Scalar_String'],
		'complex' => ['Expr_Array'],
		'func' => ['Expr_FuncCall'],
	];
	public static function getAllType()
	{
		$ret = [];
		foreach (self::$map as $types) {
			$ret = array_merge($ret, $types);
		}
		return $ret;
	}
	public static function getType($class)
	{
		foreach (self::$map as $type => $values) {
			if (in_array($class, $values)) {
				return $type;
			}
		}
		throw new Exception("no type for '$class'", 1);
	}
	public static function createFromExpr($expr)
	{
		$v = new self();
		return self::_addExpr($v, $expr);
	}
	public static function _addExpr(Value $v, PhpParser\NodeAbstract $expr)
	{
		if ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
			$v->types = self::getAllType();
			return $v;
		} elseif ($expr instanceof PhpParser\Node\Expr\New_) {
			$v->types[] = $expr->class->name->parts[0];
		}
		$t = $expr->getType();
		$type = self::getType($t);
		switch ($type) {
			case 'scalar':
				$v->types[] = $t;
				break;
			case 'complex':
				$v->types[] = $t;
				break;
			case 'func':
				$v->types = array_merge($v->types, Func::getPossibleTypes($expr));
				break;
			
			default:
				throw new Exception("unkown '$type' of '$t'", 1);
				break;
		}
		$v->types = array_unique($v->types);
		// print_r($v->types);
		return $v;
	}
	public function addExpr($expr)
	{
		self::_addExpr($this, $expr);
	}
	public function addType($type)
	{
		$this->types[] = $type;
	}
	public function addValue($value)
	{
		$this->values[] = $value;
	}
}
