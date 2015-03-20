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
    $tf->assertEquals($env['a']->type->types, ['Scalar_LNumber']);
    $tf->assertEquals($env['b']->type->types, ['Scalar_String']);
    $tf->assertEquals($env['c']->type->types, ['Expr_Array']);
    $tf->assertEquals($env['d']->type->types, ['stdClass']);
    $tf->assertEquals($env['e']->type->types, ['Resourse']);
    $tf->assertEquals($env['f']->type->types, ['Scalar_String']);
    $tf->assertEquals($env['g']->type->types, ['Scalar_String']);
    $tf->assertEquals($env['h']->type->types, ['Scalar_String']);
    $tf->assertEquals($env['i']->type->types, ['Scalar_String']);
    $tf->assertEquals($env['j']->type->types, ['Scalar_String']);
    $tf->assertEquals($env['k']->type->types, ['Scalar_String']);
    $tf->assertEquals($env['l']->type->types, ['Scalar_String']);
    $tf->assertEquals($env['m']->type->types, ['Scalar_String']);
});

$tf();
