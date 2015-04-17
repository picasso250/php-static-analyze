<?php

require (__DIR__).'/vendor/autoload.php';
require (__DIR__).'/autoload.php';
require (__DIR__).'/logic.php';

$table = [];
handle_dir($argv[1], 'read_func'); // build
handle_dir($argv[1], 'consume_func'); // consume

function handle_dir($dir, $callback)
{
    if ($handle = opendir($dir)) {
        /* 这是正确地遍历目录方法 */
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file === '..') {
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
                    echo "find method $s->name\n";
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
    global $table;
    $code = file_get_contents($file);
    $parser = new PhpParser\Parser(new PhpParser\Lexer);
    $stmts = $parser->parse($code);
    foreach ($stmts as $stmt) {
        print_r($stmt);exit;
        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            echo "class $stmt->name\n";
            foreach ($stmt->stmts as $s) {
                if ($s instanceof PhpParser\Node\Stmt\ClassMethod) {
                    echo "find method $s->name\n";
                    $table[$stmt->name][] = $s->name;
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
