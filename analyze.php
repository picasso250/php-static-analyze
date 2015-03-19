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
            if ($cond instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                // fix: left and right can be exchange
                $left = $cond->left;
                if ($left instanceof PhpParser\Node\Expr\Variable) {
                    if (isset($table[$left->name])) {
                        $value = $table[$left->name];
                        $right = $cond->right;
                        if ($right instanceof PhpParser\Node\Expr\ConstFetch) {
                            $type = get_const_type($right);
                            if (!in_array($type, $value->types)) {
                                warning(
                                    "'$$left->name' can only be (%s), but compare to $type in %d-%d",
                                    implode(',', $value->types),
                                    $cond->getAttribute('startLine'),
                                    $cond->getAttribute('endLine')
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    // print_r($table);
}
function get_const_type($const)
{
    $name = $const->name->parts[0];
    if (strtolower($name) === 'null') {
        return 'NULL';
    }
    throw new Exception("unkown const type", 1);
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
