<?php

class Value
{
    public $type;
    // public $isAny = true;
    // public $values = [];

    public function __construct()
    {
        $this->type = Type::createUnknown();
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

    public static function createFromExpr($expr)
    {
        $v = new self();
        return self::_addExpr($v, $expr);
    }
    public static function isDirect($type)
    {
        return in_array($type, [
            'Scalar_LNumber', 'Scalar_LNumber', 'Scalar_String',
            'Expr_Array']);
    }
    public static function _addExpr(Value $v, PhpParser\NodeAbstract $expr)
    {
        $type = $expr->getType();
        if ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            // todo
            $v->type = Type::createUnknown();
        } elseif ($expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            // todo
            $v->type = Type::createUnknown();
        } elseif ($expr instanceof PhpParser\Node\Expr\New_) {
            // fix: dynamic class
            $v->type->addType($expr->class->parts[0]);
        } elseif (Operator::isArithmetic(get_class($expr))) {
            // fix: array +
            // fix: int or float
            $v->type->addType('Scalar_DNumber');
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            // todo bool
            $v->type->addType('Boolean');
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $v->type->addType('Scalar_String');
        } elseif ($expr instanceof PhpParser\Node\Expr\FuncCall) {
            $v->type->extend(Func::getPossibleTypes($expr));
        } elseif (self::isDirect($type)) {
            $v->type->addType($type);
        } elseif (isset(self::$map[$type])) {
            $v->type->addType(self::$map[$type]);
        } else {
            throw new Exception("unkown '$type' of ".get_class($expr), 1);
        }
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
