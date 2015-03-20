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
    public static function createFromTypes(array $types)
    {
        $v = new self();
        $v->types = $types;
        return $v;
    }
    public static function createFromExpr($expr)
    {
        $v = new self();
        return self::_addExpr($v, $expr);
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

    public static function isDirect($type)
    {
        return in_array($type, [
            'Scalar_LNumber', 'Scalar_DNumber', 'Scalar_String',
            'Expr_Array']);
    }
    public static function _addExpr(Type $t, PhpParser\NodeAbstract $expr)
    {
        $type = $expr->getType();
        if ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            // todo
            $t = Type::createUnknown();
        } elseif ($expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            // todo
            $t = Type::createUnknown();
        } elseif ($expr instanceof PhpParser\Node\Expr\New_) {
            // fix: dynamic class
            $t->addType($expr->class->parts[0]);
        } elseif (Operator::isArithmetic(get_class($expr))) {
            // fix: array +
            // fix: int or float
            $t->addType('Scalar_DNumber');
        } elseif (Operator::isBitwise(get_class($expr))) {
            $t->addType('Scalar_LNumber');
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            // todo bool
            $t->addType('Boolean');
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $t->addType('Scalar_String');
        } elseif ($expr instanceof PhpParser\Node\Expr\FuncCall) {
            $t->extend(Func::getPossibleTypes($expr));
        } elseif (self::isDirect($type)) {
            $t->addType($type);
        } elseif (isset(self::$map[$type])) {
            $t->addType(self::$map[$type]);
        } else {
            throw new Exception("unkown '$type' of ".get_class($expr), 1);
        }
        return $t;
    }
    public function addExpr($expr)
    {
        self::_addExpr($this, $expr);
    }
}
