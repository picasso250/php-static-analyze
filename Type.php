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
        $class = get_class($expr);
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
        } elseif (Operator::isArithmetic($class)) {
            // fix: array +
            // fix: int or float
            $t->addType('Scalar_DNumber');
        } elseif (Operator::isBitwise($class)) {
            $t->addType('Scalar_LNumber');
        } elseif (Operator::isComparison($class)) {
            $t->addType('Boolean');
        } elseif (Operator::isLogical($class)) {
            $t->addType('Boolean');
        } elseif ($expr instanceof PhpParser\Node\Expr\Instanceof_) {
            $t->addType('Boolean');
        } elseif (Operator::isIncrOrDecr($class)) {
            $t->addType('Scalar_DNumber');
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $t->addType('Scalar_String');
        } elseif ($expr instanceof PhpParser\Node\Expr\FuncCall) {
            $t->extend(Func::getPossibleTypes($expr));
        } elseif ($expr instanceof PhpParser\Node\Expr\ConstFetch) {
            $parts = $expr->name->parts;
            if (count($parts) === 1) {
                if (strtolower($parts[0]) === 'null') {
                    $t->addType('NULL');
                } elseif (strtolower($parts[0]) === 'false' || strtolower($parts[0]) === 'true') {
                    $t->addType('Boolean');
                }
            } else {
                error_log("unkown $parts[0]");
                $t->isAny = true;
            }
        } elseif (self::isDirect($type)) {
            $t->addType($type);
        } elseif (isset(self::$map[$type])) {
            $t->addType(self::$map[$type]);
        } else {
            throw new Exception("unkown '$type' of ".$class, 1);
        }
        return $t;
    }
    public function addExpr($expr)
    {
        self::_addExpr($this, $expr);
    }
}
