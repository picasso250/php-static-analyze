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
    check_inner_type($stmts[0]->stmts);

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

function _check_inner_type($stmts)
{
    foreach ($stmts as $stmt) {
        # code...
    }
}
function check_inner_type($stmts)
{
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            // fix: list(a, b) = $a;
            if (isset($table[$stmt->var->name])) {
                // echo "addExpr {$stmt->var->name}\n";
                $table[$stmt->var->name]->addExpr($stmt->expr);
            } else {
                $table[$stmt->var->name] = Value::createFromExpr($stmt->expr);
            }
        }
    }
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\If_) {
            $cond = $stmt->cond;
            // print_r($cond);exit;
            // fix: && ||
            if ($cond instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                check_Identical($cond, $table);
            } elseif (is_bool_op_expr($cond)) {
                check_Identical($cond->left, $table);
                check_Identical($cond->right, $table);
            }
        }
    }
    // print_r($table);
}
function is_bool_op_expr($expr)
{
    return in_array(get_class($expr), ['PhpParser\Node\Expr\BinaryOp\BooleanAnd']);
}
function check_Identical(PhpParser\Node\Expr\BinaryOp\Identical $idt, $env)
{
    $left = get_possible_type($idt->left, $env);
    $right = get_possible_type($idt->right, $env);
    if (!array_intersect($left, $right)) {
        warning(
            "compare %s === %s, but type not match (%s) === (%s) in line %d-%d",
            repr($idt->left),
            repr($idt->right),
            implode(',', $left),
            implode(',', $right),
            $idt->getAttribute('startLine'),
            $idt->getAttribute('endLine')
        );
    }
}
function repr($token)
{
    if ($token instanceof PhpParser\Node\Expr\Variable) {
        return "$$token->name";
    } elseif ($token instanceof PhpParser\Node\Expr\ConstFetch) {
        return $name = $token->name->parts[0];
    }
    throw new Exception("unkown type ".get_class($token), 1);
}
function get_possible_type($expr, $env)
{
    if ($expr instanceof PhpParser\Node\Expr\Variable) {
        if (isset($env[$expr->name])) {
            $value = $env[$expr->name];
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
    exit(1);
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
