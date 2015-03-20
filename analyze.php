<?php

require 'vendor/autoload.php';
require 'Func.php';
require 'Value.php';

$file = $argv[1];
$code = file_get_contents($file);

$parser = new PhpParser\Parser(new PhpParser\Lexer);
try {
    $stmts = $parser->parse($code);
    // unreachable($stmts[0]->stmts);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            check_inner_type($stmt->stmts);
        }
    }
} catch (PhpParser\Error $e) {
    echo 'Parse Error: ', $e->getMessage();
}

function get_all_func(array $stmts)
{
    $ret = [];
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $ret[] = Func::createFromFunction($stmt);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            foreach ($stmt->stmts as $s) {
                if ($s instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $ret[] = Func::createFromClassMethod($stmt, $s);
                }
            }
        }
    }
    return $ret;
}

function build_env($stmts)
{
    $table = [];
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            // fix: list(a, b) = $a;
            if (isset($table[$stmt->var->name])) {
                // echo "addExpr {$stmt->var->name}\n";
                $table[$stmt->var->name]->addExpr($stmt->expr);
            } else {
                // echo "createFromExpr {$stmt->var->name}\n";
                // echo $stmt->getAttribute('startLine'), "\n";
                $table[$stmt->var->name] = Value::createFromExpr($stmt->expr);
            }
        }
    }
    return $table;
}
function check_inner_type($stmts)
{
    $env = build_env($stmts);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\If_) {
            $cond = $stmt->cond;
            // print_r($cond);exit;
            check_cond($cond, $env);
        }
    }
}
function check_cond($cond, $env)
{
    if (is_equal_expr($cond)) {
        check_Identical($cond, $env);
    } elseif (is_bool_op_expr($cond)) {
        foreach ($cond->getIterator() as $expr) {
            // echo get_class($cond),"\n";
            check_cond($expr, $env);
        }
    }
}
function is_equal_expr($expr)
{
    return in_array(get_class($expr), [
        'PhpParser\Node\Expr\BinaryOp\Equal',
        'PhpParser\Node\Expr\BinaryOp\NotEqual',
        'PhpParser\Node\Expr\BinaryOp\Identical',
        'PhpParser\Node\Expr\BinaryOp\NotIdentical',
    ]);
}
function is_bool_op_expr($expr)
{
    return in_array($expr->getType(), [
        'Expr_BinaryOp_BooleanAnd',
        'Expr_BinaryOp_BooleanOr',
    ]);
}
function check_Identical(PhpParser\Node\Expr\BinaryOp $idt, $env)
{
    $left = get_possible_type($idt->left, $env);
    $right = get_possible_type($idt->right, $env);
    if (!array_intersect($left, $right)) {
        warning(
            "compare %s, but type not match (%s) === (%s) in line %d-%d",
            repr($idt),
            implode(', ', $left),
            implode(', ', $right),
            $idt->getAttribute('startLine'),
            $idt->getAttribute('endLine')
        );
    }
}
function repr($expr)
{
    if ($expr instanceof PhpParser\Node\Expr\Variable) {
        return "$$expr->name";
    } elseif ($expr instanceof PhpParser\Node\Expr\ConstFetch) {
        return $name = $expr->name->parts[0];
    } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp) {
        $map = [
            'PhpParser\Node\Expr\BinaryOp\Equal' => '==',
            'PhpParser\Node\Expr\BinaryOp\NotEqual' => '!=',
            'PhpParser\Node\Expr\BinaryOp\Identical' => '===',
            'PhpParser\Node\Expr\BinaryOp\NotIdentical' => '!==',
        ];
        return implode(' ', [repr($expr->left), $map[get_class($expr)], repr($expr->right)]);
    }
    throw new Exception("unkown type ".get_class($expr), 1);
}
function get_possible_type($expr, $env)
{
    if ($expr instanceof PhpParser\Node\Expr\Variable) {
        if (isset($env[$expr->name])) {
            $value = $env[$expr->name];
            // echo "$$expr->name: ";
            // print_r($value);
            return $value->types;
        } else {
            warning("uninitialized var $expr->name");
        }
    } elseif ($expr instanceof PhpParser\Node\Expr\ConstFetch) {
        $type = [get_const_type($expr)];
        return $type;
    }
    // fix: const and not const, such as true false
    throw new Exception("unkown type ".get_class($expr), 1);
}
function get_const_type($const)
{
    $name = $const->name->parts[0];
    if (strtolower($name) === 'null') {
        return 'NULL';
    }
    throw new Exception("unkown const type ".get_class($const), 1);
}
function warning()
{
    $str = call_user_func_array('sprintf', func_get_args());
    echo "Warning: $str\n";
}
function unreachable($stmts)
{
    $already_return = false;
    foreach ($stmts as $stmt) {
        if ($already_return) {
            echo "error\n";
            print_r($stmt);
        }
        if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
            $already_return = true;
        }
    }
}
