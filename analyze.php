<?php

require 'vendor/autoload.php';
require 'Func.php';

$file = $argv[1];
$code = file_get_contents($file);

$parser = new PhpParser\Parser(new PhpParser\Lexer);
try {
    $stmts = $parser->parse($code);
    foreach ($stmts as $stmt) {
    	$last = $stmt;
    }
    print_r($last);
    // print_r(get_all_return($last));
    // $stmts is an array of statement nodes
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
function get_all_return(PhpParser\Node\Stmt\Function_ $func)
{
	return array_filter($func->stmts, function($s) {
			return $s instanceof PhpParser\Node\Stmt\Return_;
		});
}
function get_return_type(PhpParser\Node\Stmt\Function_ $func)
{
	$a = 1;
	return 1;
}

