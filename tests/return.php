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
    $tf->data->return_null = $stmts[0];
    $tf->data->only_return = $stmts[1];
    $tf->data->no_return = $stmts[2];
    $tf->data->two_return = $stmts[3];
    $tf->data->if_return = $stmts[4];
    $tf->data->while_return = $stmts[5];
});

$tf->test("Testing the return", function($tf) {
    $return_null = Func::createFromFunction($tf->data->return_null);
    $tf->assertEquals($return_null->getReturnType(), ['NULL']);
});

$tf();
