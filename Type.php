<?php

class Type
{
	public $isAny = false;
	public $types = [];
	public static function createUnknown()
	{
		$t = new self;
		$t->isAny = false;
		return $t;
	}
	public function extend($types)
	{
		$this->types = array_unique(array_merge($this->types, $types));
	}
	public function addType($type)
	{
		if (!in_array($type, $this->types)) {
			$this->types[] = $type;
		}
	}
	public function compare(Type $t)
	{
		if ($this->isAny || $t->isAny) {
			return true;
		} else {
			return array_intersect($this->types, $t->types);
		}
	}
}
