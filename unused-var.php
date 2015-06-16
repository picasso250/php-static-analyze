<?php

if (!isset($argv[1])) {
    echo <<<EOF
Find unused variables

    Usage:
        $argv[0] -d <code_dir>
        $argv[0] <file>

EOF;
    exit(1);
}

function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

require (__DIR__).'/vendor/autoload.php';
require (__DIR__).'/autoload.php';
require (__DIR__).'/logic.php';

if (get_arg_bool('-l')) {
    ini_set('error_log', __DIR__.'/php_error.log');
}

$table = [];
$table_lib = [];
$class_hierarchy = [];
handle_file($argv[1]);
exit;
handle_dir($argv[1], 'read_func'); // build
exit;

handle_dir($argv[1], 'consume_func'); // consume
// print_r($table);

foreach ($table as $c => $value) {
    foreach ($value as $m => $count) {
        if ($count == 0 && !is_method_ignore($m)) {
            echo "$c::$m() not used\n";
        }
    }
}

function get_arg_bool($name)
{
    global $argv;
    foreach ($argv as $i => $s) {
        if ($s === $name) {
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

function handle_file($file)
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
                    echo "find method $s->name\n";
                    process_function($s);
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


function process_function($s)
{
    error_log(__FUNCTION__." $s->name()");
    $table = [];
    foreach ($s->params as $param) {
        declare_var($param, $table);
    }
    consume_stmts($s->stmts, $table);
    print_r($table);
    foreach ($table['use_var'] as $name => $c) {
        if ($c === 0) {
            $attrs = $s->getAttributes();
            echo "\$$name not used in function $s->name()\n";
            echo get_file_line($attrs['startLine'], $attrs['endLine']);
            throw new Exception("Error Processing Request", 1);
        }
    }
}
function consume_stmts($stmts, &$table)
{
    foreach ($stmts as $stmt) {
        // print_r($stmt);exit;
        // error_log("consume statement: ".get_class($stmt));
        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            throw new Exception("Error Processing Request", 1);
        }
        if ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
            foreach ($stmt->vars as $var) {
                expr_use_var($var, $table);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
            foreach ($stmt->exprs as $expr) {
                expr_use_var($expr, $table);
            }
        } elseif (is_prefix($stmt, 'PhpParser\\Node\\Expr\\')) {
            expr_use_var($stmt, $table);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            expr_use_var($stmt->expr, $table, $table);
            change_var($stmt->var, $table);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_ || $stmt instanceof PhpParser\Node\Stmt\Throw_) {
            if ($stmt->expr) {
                expr_use_var($stmt->expr, $table);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
            // error_log("for");
            consume_stmts($stmt->init, $table);
            foreach ($stmt->cond as $cond) {
                expr_use_var($cond, $table);
            }
            foreach ($stmt->loop as $loop) {
                expr_use_var($loop, $table);
            }
            consume_stmts($stmt->stmts, $table);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
            declare_var($stmt->keyVar, $table);
            declare_var($stmt->valueVar, $table);
            expr_use_var($stmt->expr, $table);
            consume_stmts($stmt->stmts, $table);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\While_ || $stmt instanceof PhpParser\Node\Stmt\Do_) {
            // error_log("while or do");
            expr_use_var($stmt->cond, $table);
            consume_stmts($stmt->stmts, $table);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
            // error_log("consume if");
            expr_use_var($stmt->cond, $table, $table);
            consume_stmts($stmt->stmts, $table);
            if ($stmt->elseifs) {
                // error_log("elseifs");
                foreach ($stmt->elseifs as $elseif) {
                    expr_use_var($elseif->cond, $table);
                    consume_stmts($elseif->stmts, $table);
                }
            }
            if ($stmt->else) {
                // error_log("else");
                consume_stmts($stmt->else->stmts, $table);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
            expr_use_var($stmt->cond, $table);
            foreach ($stmt->cases as $case) {
                if ($case->cond) {
                    expr_use_var($case->cond, $table);
                }
                consume_stmts($case->stmts, $table);
            }
        } elseif (is_ignore_stmt($stmt)) {
            // do nothing
        } elseif ($stmt->stmts || $stmt->stmts === array()) {
            consume_stmts($stmt->stmts, $table);
        } else {
            print_r($stmt);;
            throw new Exception("unknown stmt", 1);
        }
    }
}
function get_file_line($startLine, $endLine)
{
    static $cache;
    if (empty($cache)) {
        $cache = file($GLOBALS['argv'][1]);
    }
    $ret = '';
    foreach ($cache as $i => $line) {
        $n = $i+1;
        if ($startLine <= $n && $n <= $endLine) {
            $ret .= $line;
        }
        if ($n === $endLine) {
            break;
        }
    }
    return $ret;
}
function change_var($var, &$table, $init = 1)
{
    if ($var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
        if (($var->dim)) {
            expr_use_var($var->dim, $table);
        }
        change_var($var->var, $table, $init);
    } elseif ($var instanceof PhpParser\Node\Expr\Variable) {
        declare_var($var, $table);
        $name = $var->name;
        $attrs = $var->getAttributes();
        error_log("\$$name changed in $attrs[startLine] - $attrs[endLine]");
        error_log(get_file_line($attrs['startLine'], $attrs['endLine']));
        if (isset($table['change_var'][$name])) {
            $table['change_var'][$name]++;
        } else {
            $table['change_var'][$name] = $init;
        }
    } elseif ($var instanceof PhpParser\Node\Expr\List_) {
        foreach ($var->vars as $v) {
            use_var($v, $table, $init);
        }
    } else {
        print_r($var);
        throw new Exception("var", 1);
    }
}
function use_var($var, &$table, $init = 1)
{
    if ($var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
        if (($var->dim)) {
            expr_use_var($var->dim, $table);
        }
        use_var($var->var, $table, $init);
    } elseif ($var instanceof PhpParser\Node\Expr\Variable) {
        $name = $var->name;
        if (!isset($table['declare_var'][$name])) {
            throw new Exception("\$$name not declared but used", 1);
        }
        $attrs = $var->getAttributes();
        error_log("\$$name used in $attrs[startLine] - $attrs[endLine]");
        error_log(get_file_line($attrs['startLine'], $attrs['endLine']));
        if (isset($table['use_var'][$name])) {
            $table['use_var'][$name]++;
        } else {
            $table['use_var'][$name] = $init;
        }
    } elseif ($var instanceof PhpParser\Node\Expr\List_) {
        foreach ($var->vars as $v) {
            use_var($v, $table, $init);
        }
    } else {
        print_r($var);
        throw new Exception("var", 1);
    }
}
function declare_var($var, &$table)
{
    if ($var instanceof PhpParser\Node\Expr\Variable || $var instanceof PhpParser\Node\Param) {
        $name = $var->name;
        if (!isset($table['declare_var'][$name])) {
            $table['declare_var'][$name] = $var;
            $table['use_var'][$name] = 0;
            $attrs = $var->getAttributes();
            error_log("\$$name declared in $attrs[startLine] - $attrs[endLine]");
        }
    } elseif ($var instanceof PhpParser\Node\Expr\StaticCall) {

    } elseif ($var instanceof PhpParser\Node\Expr\PropertyFetch) {
        assert(is_string($var->name));
        declare_var($var->var, $table);
    } elseif ($var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
        declare_var($var->var, $table);
    } else {
        print_r($var);
        throw new Exception("var ", 1);
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
function expr_use_var($expr, &$table)
{
    if (is_prefix($expr, 'PhpParser\\Node\\Expr\\BinaryOp')) {
        expr_use_var($expr->left, $table);
        expr_use_var($expr->right, $table);
    } elseif (is_single_expr($expr)) {
        expr_use_var($expr->expr, $table);
    } elseif (is_prefix($expr, 'PhpParser\\Node\\Expr\\AssignOp\\')) {
        error_log("AssignOp");
        expr_use_var($expr->expr, $table, $table);
        use_var($expr->var, $table);
    } elseif ($expr instanceof PhpParser\Node\Expr\Empty_) {
        // $expr = $expr->expr;
        // if ($expr->name) {
        //     use_var($expr, $table, 0);
        // } else {
        //     use_var($expr, $table, 0);
        // }
    } elseif ($expr instanceof PhpParser\Node\Expr\Assign) {
        expr_use_var($expr->expr, $table, $table);
        change_var($expr->var, $table);
    } elseif ($expr instanceof PhpParser\Node\Expr\Instanceof_) {
        expr_use_var($expr->expr, $table);
        expr_use_var($expr->class, $table);
    } elseif ($expr instanceof PhpParser\Node\Expr\Ternary) {
        expr_use_var($expr->cond, $table);
        if ($expr->if) {
            expr_use_var($expr->if, $table);
        }
        expr_use_var($expr->else, $table);
    } elseif (is_ignore_expr($expr)) {
        // do nothing
    } elseif ($expr instanceof PhpParser\Node\Expr\Closure) {
        assert(count($expr->uses) === 0);
        process_function($expr);
    } elseif ($expr instanceof PhpParser\Node\Expr\Isset_) {
        // foreach ($expr->vars as $var) {
        //     declare_var($var, $table);
        // }
    } elseif ($expr instanceof PhpParser\Node\Expr\Variable) {
        use_var($expr, $table, 1);
    } elseif ($expr instanceof PhpParser\Node\Expr\New_) {
        foreach ($expr->args as $arg) {
            expr_use_var($arg, $table);
        }
        assert(count($expr->class->parts) === 1);
    } elseif ($expr instanceof PhpParser\Node\Expr\Include_
        || $expr instanceof PhpParser\Node\Expr\Exit_) {
        print_r($expr);exit;
    } elseif ($expr instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
        assert(count($expr->class->parts) === 1);
    } elseif ($expr instanceof PhpParser\Node\Expr\PropertyFetch) {
        expr_use_var($expr->var, $table);
    } elseif ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
        expr_use_var($expr->var, $table);
        expr_use_var($expr->dim, $table);
    } elseif ($expr instanceof PhpParser\Node\Expr\ArrayItem) {
        expr_use_var($expr->value, $table);
    } elseif ($expr instanceof PhpParser\Node\Expr\Array_) {
        foreach ($expr->items as $item) {
            expr_use_var($item, $table);
        }
    } elseif (is_expr($expr, ['PreInc', 'PostInc', 'PreDec', 'PostDec'])) {
        expr_use_var($expr->var, $table);
    } elseif ($expr instanceof PhpParser\Node\Expr\StaticCall || $expr instanceof PhpParser\Node\Expr\MethodCall || $expr instanceof PhpParser\Node\Expr\FuncCall) {
        foreach ($expr->args as $arg) {
            expr_use_var($arg, $table);
        }
    } elseif ($expr instanceof PhpParser\Node\Arg) {
        if ($expr->value) {
            expr_use_var($expr->value, $table);
        } else {
            throw new Exception("Error Processing Request", 1);
        }
    } else {
        print_r($expr);
        throw new Exception("unknown expr", 1);
    }
}

function is_ignore_expr($expr)
{
    return is_prefix($expr, 'PhpParser\\Node\\Scalar\\')
        || $expr instanceof PhpParser\Node\Expr\ConstFetch
        || $expr instanceof PhpParser\Node\Name
        || $expr instanceof PhpParser\Node\Expr\ClassConstFetch;
}
function is_single_expr($expr)
{
    return is_prefix($expr, 'PhpParser\\Node\\Expr\\Cast\\')
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
