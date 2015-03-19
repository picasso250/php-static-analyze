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
	public static function getType($class)
	{
		$map = [
			'scalar' => ['PhpParser\Node\Scalar\LNumber', 'PhpParser\Node\Scalar\String'],
			'complex' => [],
			'func' => ['PhpParser\Node\Expr\FuncCall'],
		];
		foreach ($map as $type => $values) {
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
	public static function _addExpr(Value $v, $expr)
	{
		$c = get_class($expr);
		$type = self::getType($c);
		switch ($type) {
			case 'scalar':
				$v->types[] = $c;
				break;
			case 'complex':
				$v->types[] = $c;
				break;
			case 'func':
				$v->types = array_merge($v->types, Func::getPossibleTypes($expr));
				break;
			
			default:
				throw new Exception("unkown '$type' of '$c'", 1);
				break;
		}
		$v->types = array_unique($v->types);
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
