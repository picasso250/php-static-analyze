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

$tf->test("Testing the Arithmetic Operators", function($tf) {
    $tf->assertEquals($tf->data->env['Negation']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['Addition']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['Subtraction']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['Multiplication']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['Division']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['Modulus']->types, ['Scalar_DNumber']); // fix Modulus always return int
});

$tf->test("Testing the Arithmetic Assign Operators", function($tf) {
    $tf->assertEquals($tf->data->env['AdditionAssign']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['SubtractionAssign']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['MultiplicationAssign']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['DivisionAssign']->types, ['Scalar_DNumber']);
    $tf->assertEquals($tf->data->env['ModulusAssign']->types, ['Scalar_DNumber']); // fix Modulus always return int
});

$tf->test("Testing the Bitwise Operators", function($tf) {
    $tf->assertEquals($tf->data->env['And']->types, ['Scalar_LNumber']);
    $tf->assertEquals($tf->data->env['Or']->types, ['Scalar_LNumber']);
    $tf->assertEquals($tf->data->env['Xor']->types, ['Scalar_LNumber']);
    $tf->assertEquals($tf->data->env['Not']->types, ['Scalar_LNumber']);
    $tf->assertEquals($tf->data->env['Shift_left']->types, ['Scalar_LNumber']);
    $tf->assertEquals($tf->data->env['Shift_right']->types, ['Scalar_LNumber']);
});

$tf->test("Testing the Comparison Operators", function($tf) {
    $tf->assertEquals($tf->data->env['Equal']->types, ['Boolean']);
    $tf->assertEquals($tf->data->env['Identical']->types, ['Boolean']);
    $tf->assertEquals($tf->data->env['Not_equal']->types, ['Boolean']);
    $tf->assertEquals($tf->data->env['Not_equal']->types, ['Boolean']);
    $tf->assertEquals($tf->data->env['Not_identical']->types, ['Boolean']);
    $tf->assertEquals($tf->data->env['Less_than']->types, ['Boolean']);
    $tf->assertEquals($tf->data->env['Greater_than']->types, ['Boolean']);
    $tf->assertEquals($tf->data->env['Less_than_or_equal_to']->types, ['Boolean']);
    $tf->assertEquals($tf->data->env['Greater_than_or_equal_to']->types, ['Boolean']);
});

$tf();
