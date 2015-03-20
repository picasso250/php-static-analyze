<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/autoload.php';
require dirname(__DIR__).'/logic.php';

use Testify\Testify;

$tf = new Testify("Danmu Test Suite");

$tf->test("Testing the literal", function($tf) {
    
    $file = basename(__FILE__, '.php').'.code.php';

    $code = file_get_contents($file);

    $parser = new PhpParser\Parser(new PhpParser\Lexer);
    $stmts = $parser->parse($code);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $env = build_env($stmt->stmts);
        }
    }
    $tf->assertEquals($env['a']->types, ['Scalar_LNumber']);
    $tf->assertEquals($env['b']->types, ['Scalar_String']);
    $tf->assertEquals($env['c']->types, ['Expr_Array']);
    $tf->assertEquals($env['d']->types, ['stdClass']);
    $tf->assertEquals($env['e']->types, ['Resourse']);
    $tf->assertEquals($env['f']->types, ['Scalar_String']);
    $tf->assertEquals($env['g']->types, ['Scalar_String']);
    $tf->assertEquals($env['h']->types, ['Scalar_String']);
    $tf->assertEquals($env['i']->types, ['Scalar_String']);
    $tf->assertEquals($env['j']->types, ['Scalar_String']);
    $tf->assertEquals($env['k']->types, ['Scalar_String']);
    $tf->assertEquals($env['l']->types, ['Scalar_String']);
    $tf->assertEquals($env['m']->types, ['Scalar_String']);
});

$tf();
