<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/autoload.php';
require dirname(__DIR__).'/logic.php';

use Testify\Testify;

$tf = new Testify("PHPTypeChecker Test Suite");

$tf->beforeEach(function($tf){
    $file = basename(__FILE__, '.php').'.code.php';

    $code = file_get_contents($file);

    $parser = new PhpParser\Parser(new PhpParser\Lexer);
    $stmts = $parser->parse($code);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $tf->data->env = build_env($stmt->stmts);
        }
    }
});

$tf->test("Testing the Parantheses Operators", function($tf) {
    $tf->assertEquals($tf->data->env['p']->types, ['Scalar_DNumber']);
});

$tf();
