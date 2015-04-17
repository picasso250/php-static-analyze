<?php

require (__DIR__).'/vendor/autoload.php';
require (__DIR__).'/autoload.php';
require (__DIR__).'/logic.php';

$table = [];
$ignore = ['vendor'];
handle_dir($argv[1], 'read_func'); // build
handle_dir($argv[1], 'consume_func'); // consume
print_r($table);

function handle_dir($dir, $callback)
{
    global $ignore;
    if ($handle = opendir($dir)) {
        /* 这是正确地遍历目录方法 */
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file === '..' || in_array($file, $ignore)) {
                continue;
            }
            if (is_dir("$dir/$file")) {
                handle_dir("$dir/$file", $callback);
            } elseif (preg_match('/\.php$/', $file)) {
                echo "process $dir/$file\n";
                $callback("$dir/$file");
            }
        }
        closedir($handle);
    }
}

function read_func($file)
{
    global $table;
    $code = file_get_contents($file);
    $parser = new PhpParser\Parser(new PhpParser\Lexer);
    $stmts = $parser->parse($code);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            echo "class $stmt->name\n";
            foreach ($stmt->stmts as $s) {
                if ($s instanceof PhpParser\Node\Stmt\ClassMethod) {
                    // echo "find method $s->name\n";
                    $table[$stmt->name][$s->name] = 0;
                } else {
                    if (! $s instanceof PhpParser\Node\Stmt\Property && ! $s instanceof PhpParser\Node\Stmt\ClassConst) {
                        print_r($s);
                        throw new Exception("what?", 1);
                    }
                }
            }
        }
    }
}
function consume_func($file)
{
    $code = file_get_contents($file);
    $parser = new PhpParser\Parser(new PhpParser\Lexer);
    $stmts = $parser->parse($code);
    _consume_func($stmts);
}
function _consume_func($stmts)
{
    foreach ($stmts as $stmt) {
        if ($stmt->stmts || $stmt->stmts === array()) {
            _consume_func($stmt->stmts);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
            foreach ($stmt->vars as $var) {
                expr_consume($var);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
            foreach ($stmt->exprs as $expr) {
                expr_consume($expr);
            }
        } elseif (is_prefix($stmt, 'PhpParser\\Node\\Expr\\')) {
            expr_consume($stmt);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_ || $stmt instanceof PhpParser\Node\Expr\Assign || $stmt instanceof PhpParser\Node\Stmt\Throw_) {
            if ($stmt->expr) {
                expr_consume($stmt->expr);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
            expr_consume($stmt->cond);
            foreach ($stmt->cases as $case) {
                if ($case->cond) {
                    expr_consume($case->cond);
                }
                _consume_func($case->stmts);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst || $stmt instanceof PhpParser\Node\Stmt\Continue_ || $stmt instanceof PhpParser\Node\Stmt\Break_ || $stmt instanceof PhpParser\Node\Stmt\Static_ || $stmt instanceof PhpParser\Node\Stmt\Property) {
            // do nothing
        } else {
            print_r($stmt);;
            throw new Exception("unknown stmt", 1);
        }
    }
}
function expr_stmt_consume($es)
{
}
function expr_consume($expr)
{
    global $table;
    if (is_prefix($expr, 'PhpParser\\Node\\Expr\\BinaryOp')) {
        expr_consume($expr->left);
        expr_consume($expr->right);
    } elseif (is_prefix($expr, 'PhpParser\\Node\\Expr\\Cast\\') || $expr instanceof PhpParser\Node\Expr\Assign || is_prefix($expr, 'PhpParser\\Node\\Expr\\AssignOp\\') || $expr instanceof PhpParser\Node\Expr\Empty_) {
        expr_consume($expr->expr);
    } elseif ($expr instanceof PhpParser\Node\Expr\Ternary) {
        expr_consume($expr->cond);
        if ($expr->if) {
            expr_consume($expr->if);
        }
        expr_consume($expr->else);
    } elseif (is_prefix($expr, 'PhpParser\\Node\\Scalar\\') || $expr instanceof PhpParser\Node\Expr\ConstFetch || $expr instanceof PhpParser\Node\Expr\Variable || $expr instanceof PhpParser\Node\Expr\New_ || $expr instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
        // do nothing
    } elseif ($expr instanceof PhpParser\Node\Expr\Closure) {
        _consume_func($expr->stmts);
    } elseif ($expr instanceof PhpParser\Node\Expr\Isset_) {
        foreach ($expr->vars as $var) {
            expr_consume($var);
        }
    } elseif ($expr instanceof PhpParser\Node\Expr\PropertyFetch) {
        expr_consume($expr->var);
    } elseif ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
        expr_consume($expr->var);
        expr_consume($expr->dim);
    } elseif ($expr instanceof PhpParser\Node\Expr\ArrayItem) {
        expr_consume($expr->value);
    } elseif ($expr instanceof PhpParser\Node\Expr\Array_) {
        foreach ($expr->items as $item) {
            expr_consume($item);
        }
    } elseif (is_expr($expr, ['PreInc', 'PostInc', 'PreDec', 'PostDec'])) {
        expr_consume($expr->var);
    } elseif ($expr instanceof PhpParser\Node\Expr\StaticCall) {
        $class = $expr->class->parts[0];
        class_method_incr($class, $expr->name);
    } elseif ($expr instanceof PhpParser\Node\Expr\MethodCall) {
        all_method_incr($expr->name);
    } elseif ($expr instanceof PhpParser\Node\Expr\FuncCall) {
        // do nothing
    } else {
        print_r($expr);
        throw new Exception("unknown expr", 1);
    }
}
function is_expr($obj, $types)
{
    foreach ($types as $type) {
        $t = "PhpParser\\Node\\Expr\\$type";
        if ($obj instanceof $t) {
            return true;
        }
    }
    return false;
}
function is_prefix($obj, $prefix)
{
    if (!is_object($obj)) {
        // debug_print_backtrace();
        var_dump($obj);
        throw new Exception("not object?", 1);
        return false;
    }
    return strpos(get_class($obj), $prefix) === 0;
}
function class_method_incr($class, $method)
{
    if (isset($table[$class][$method])) {
        $table[$class][$method]++;
    } else {
        error_log("$class::$method() not found");
        all_method_incr($method);
    }
}
function all_method_incr($method)
{
    global $table;
    foreach ($table as $c => $v) {
        foreach ($v as $m => $_) {
            if ($m === $method) {
                $table[$c][$m]++;
            }
        }
    }
}
