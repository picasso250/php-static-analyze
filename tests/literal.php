<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/autoload.php';
require dirname(__DIR__).'/logic.php';

use Testify\Testify;

$tf = new Testify("Danmu Test Suite");

$tf->test("Testing the get_sub() method", function($tf) {
    
    $file = basename(__FILE__, '.php').'.code.php';

    $code = file_get_contents($file);

    $parser = new PhpParser\Parser(new PhpParser\Lexer);
    $stmts = $parser->parse($code);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $env = build_env($stmt->stmts);
        }
    }
    var_dump($env);
    $tf->assertEquals($env['a']->types, ['Scalar_LNumber']);
    $tf->assertEquals($env['b']->types, ['Scalar_String']);
    $tf->assertEquals($env['c']->types, ['Expr_Array']);
    $tf->assertEquals($env['d']->types, ['stdClass']);
    $tf->assertEquals($env['e']->types, ['Resourse']);
    $tf->assertEquals($env['f']->types, ['Scalar_String']);
});

$tf();
