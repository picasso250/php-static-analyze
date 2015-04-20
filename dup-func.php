<?php

require (__DIR__).'/vendor/autoload.php';
require (__DIR__).'/autoload.php';
require (__DIR__).'/logic.php';

$table = [];
$table_lib = [];
$class_hierarchy = [];
$ignore = ['vendor', 'data', 'views', 'htmlpurifier', 'messages'];
handle_dir($argv[1], 'read_func'); // build
if ($libs = get_arg_n('--lib')) {
    foreach ($libs as $lib) {
        handle_dir($lib, 'read_lib_func'); // build
    }
}
handle_dir($argv[1], 'consume_func'); // consume
// print_r($table);

foreach ($table as $c => $value) {
    foreach ($value as $m => $count) {
        if ($count == 0 && !is_method_ignore($m)) {
            echo "$c::$m() not used\n";
        }
    }
}

function get_arg_n($name)
{
    global $argv;
    reset($argv);
    next($argv);
    $ret = [];
    while (true) {
        $name_ = current($argv);
        if ($name_ === $name) {
            $ret[] = next($argv);
        }
        if (next($argv) === false) {
            break;
        }
    }
    return $ret;
}
function is_method_ignore($method)
{
    $ignore_method_pattern_list = [
        '/^action[A-Z0-9]/',
        '/^__/',
        '/^tableName$/',
        '/^beforeAction$/',
        '/^model$/',
    ];
    foreach ($ignore_method_pattern_list as $pattern) {
        if (preg_match($pattern, $method)) {
            return true;
        }
    }
    return false;
}
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
                error_log("process $dir/$file");
                $callback("$dir/$file");
            }
        }
        closedir($handle);
    }
}

function read_func($file)
{
    global $table;
    global $class_hierarchy;
    $code = file_get_contents($file);
    $parser = new PhpParser\Parser(new PhpParser\Lexer);
    $stmts = $parser->parse($code);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            error_log("class $stmt->name");
            if ($stmt->extends) {
                assert(count($stmt->extends->parts) == 1);
                $parent = $stmt->extends->parts[0];
                $class_hierarchy[$stmt->name] = $parent;
                error_log("$stmt->name <== $parent");
            }
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
function read_lib_func($file)
{
    global $table_lib;
    global $class_hierarchy;
    $code = file_get_contents($file);
    $parser = new PhpParser\Parser(new PhpParser\Lexer);
    $stmts = $parser->parse($code);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            error_log("class $stmt->name");
            if ($stmt->extends) {
                assert(count($stmt->extends->parts) == 1);
                $parent = $stmt->extends->parts[0];
                $class_hierarchy[$stmt->name] = $parent;
                error_log("$stmt->name <== $parent");
            }
            foreach ($stmt->stmts as $s) {
                if ($s instanceof PhpParser\Node\Stmt\ClassMethod) {
                    // echo "find method $s->name\n";
                    $table_lib[$stmt->name][$s->name] = 0;
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
    consume_stmts($stmts);
}
function consume_stmts($stmts)
{
    foreach ($stmts as $stmt) {
        // error_log("consume statement: ".get_class($stmt));
        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            $GLOBALS['current_class'] = $stmt->name;
            if ($stmt->extends) {
                assert(count($stmt->extends->parts) == 1);
                $GLOBALS['current_class_parent'] = $stmt->extends->parts[0];
            }
        }
        if ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
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
        } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
            // error_log("for");
            consume_stmts($stmt->init);
            foreach ($stmt->cond as $cond) {
                expr_consume($cond);
            }
            foreach ($stmt->loop as $loop) {
                expr_consume($loop);
            }
            consume_stmts($stmt->stmts);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
            // error_log("foreach");
            expr_consume($stmt->expr);
            consume_stmts($stmt->stmts);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\While_ || $stmt instanceof PhpParser\Node\Stmt\Do_) {
            // error_log("while or do");
            expr_consume($stmt->cond);
            consume_stmts($stmt->stmts);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
            // error_log("consume if");
            expr_consume($stmt->cond);
            consume_stmts($stmt->stmts);
            if ($stmt->elseifs) {
                // error_log("elseifs");
                foreach ($stmt->elseifs as $elseif) {
                    expr_consume($elseif->cond);
                    consume_stmts($elseif->stmts);
                }
            }
            if ($stmt->else) {
                // error_log("else");
                consume_stmts($stmt->else->stmts);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
            expr_consume($stmt->cond);
            foreach ($stmt->cases as $case) {
                if ($case->cond) {
                    expr_consume($case->cond);
                }
                consume_stmts($case->stmts);
            }
        } elseif (is_ignore_stmt($stmt)) {
            // do nothing
        } elseif ($stmt->stmts || $stmt->stmts === array()) {
            consume_stmts($stmt->stmts);
        } else {
            print_r($stmt);;
            throw new Exception("unknown stmt", 1);
        }
    }
}
function is_ignore_stmt($stmt)
{
    return $stmt instanceof PhpParser\Node\Stmt\ClassConst
        || $stmt instanceof PhpParser\Node\Stmt\Continue_
        || $stmt instanceof PhpParser\Node\Stmt\Break_
        || $stmt instanceof PhpParser\Node\Stmt\Static_
        || $stmt instanceof PhpParser\Node\Stmt\Property
        || $stmt instanceof PhpParser\Node\Stmt\Use_;
}
function expr_stmt_consume($es)
{
}
function expr_consume($expr)
{
    if (is_prefix($expr, 'PhpParser\\Node\\Expr\\BinaryOp')) {
        expr_consume($expr->left);
        expr_consume($expr->right);
    } elseif (is_single_expr($expr)) {
        expr_consume($expr->expr);
    } elseif ($expr instanceof PhpParser\Node\Expr\Instanceof_) {
        expr_consume($expr->expr);
        expr_consume($expr->class);
    } elseif ($expr instanceof PhpParser\Node\Expr\Ternary) {
        expr_consume($expr->cond);
        if ($expr->if) {
            expr_consume($expr->if);
        }
        expr_consume($expr->else);
    } elseif (is_ignore_expr($expr)) {
        // do nothing
    } elseif ($expr instanceof PhpParser\Node\Expr\Closure) {
        consume_stmts($expr->stmts);
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
        assert(count($expr->class->parts) == 1);
        $class = $expr->class->parts[0];
        if ($class == 'self') {
            $class = $GLOBALS['current_class'];
            error_log("self --> $class");
        } elseif ($class == 'parent') {
            $class = $GLOBALS['current_class_parent'];
        }
        class_method_incr($class, $expr->name);
    } elseif ($expr instanceof PhpParser\Node\Expr\MethodCall) {
        $var = $expr->var;
        if ($var->name === 'this') { // inheretation
            // error_log("this --> {$GLOBALS['current_class']}");
            class_method_incr($GLOBALS['current_class'], $expr->name);
        } elseif ($var instanceof PhpParser\Node\Expr\StaticCall && $var->name === 'model') {
            assert(count($var->class->parts) == 1);
            $name = $var->class->parts[0];
            if ($name === 'self') {
                $name = $GLOBALS['current_class'];
                // error_log("(self --> $name)::model()");
            } else {
                // error_log("$name::model()");
            }
            class_method_incr($name, $expr->name);
        } else {
            all_method_incr($expr->name);
        }
    } elseif ($expr instanceof PhpParser\Node\Expr\FuncCall) {
        // do nothing
    } else {
        print_r($expr);
        throw new Exception("unknown expr", 1);
    }
}
function is_ignore_expr($expr)
{
    return is_prefix($expr, 'PhpParser\\Node\\Scalar\\')
        || $expr instanceof PhpParser\Node\Expr\ConstFetch
        || $expr instanceof PhpParser\Node\Expr\Variable
        || $expr instanceof PhpParser\Node\Expr\New_
        || $expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
        || $expr instanceof PhpParser\Node\Expr\Include_
        || $expr instanceof PhpParser\Node\Expr\Exit_
        || $expr instanceof PhpParser\Node\Name
        || $expr instanceof PhpParser\Node\Expr\ClassConstFetch;
}
function is_single_expr($expr)
{
    return is_prefix($expr, 'PhpParser\\Node\\Expr\\Cast\\')
        || $expr instanceof PhpParser\Node\Expr\Assign
        || is_prefix($expr, 'PhpParser\\Node\\Expr\\AssignOp\\')
        || $expr instanceof PhpParser\Node\Expr\Empty_
        || $expr instanceof PhpParser\Node\Expr\BooleanNot
        || $expr instanceof PhpParser\Node\Expr\UnaryMinus
        || $expr instanceof PhpParser\Node\Expr\ErrorSuppress;
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
    global $table;
    global $table_lib;
    global $class_hierarchy;
    if (isset($table[$class][$method])) {
        error_log("$class::$method() incr");
        $table[$class][$method]++;
    } elseif (isset($table_lib[$class][$method])) {
        error_log("$class::$method() found");
        // do nothing
    } else {
        error_log("$class::$method() not found");
        if (isset($class_hierarchy[$class])) {
            $parent = $class_hierarchy[$class];
            error_log("$class ---> $parent");
            class_method_incr($parent, $method);
        } else {
            error_log("top $method()");
            all_method_incr($method);
        }
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
