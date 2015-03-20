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
		'Scalar_MagicConst_Dir' => 'Scalar_String',
		'Scalar_MagicConst_File' => 'Scalar_String',
		'Scalar_MagicConst_Line' => 'Scalar_String',
		'Scalar_MagicConst_Function' => 'Scalar_String',
		'Scalar_MagicConst_Class' => 'Scalar_String',
		'Scalar_MagicConst_Namespace' => 'Scalar_String',
		'Scalar_MagicConst_Trait' => 'Scalar_String',
		'Scalar_MagicConst_Method' => 'Scalar_String',
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
	public static function isDirect($type)
	{
		return in_array($type, ['Scalar_LNumber', 'Scalar_String', 'Expr_Array']);
	}
	public static function _addExpr(Value $v, PhpParser\NodeAbstract $expr)
	{
		$type = $expr->getType();
		if ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
			$v->types = self::getAllType();
		} elseif ($expr instanceof PhpParser\Node\Expr\New_) {
			// fix: dynamic class
			$v->types[] = $expr->class->parts[0];
		} elseif ($expr instanceof PhpParser\Node\Expr\FuncCall) {
			$v->types = array_merge($v->types, Func::getPossibleTypes($expr));
		} elseif (self::isDirect($type)) {
			$v->types[] = $type;
		} elseif (isset(self::$map[$type])) {
			$v->types[] = self::$map[$type];
		} else {
			throw new Exception("unkown '$type' of ".get_class($expr), 1);
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
