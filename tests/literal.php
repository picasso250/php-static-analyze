<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/autoload.php';
require dirname(__DIR__).'/logic.php';

$file = basename(__FILE__, '.php').'.code.php';

$code = file_get_contents($file);

$parser = new PhpParser\Parser(new PhpParser\Lexer);
try {
    $stmts = $parser->parse($code);
    // unreachable($stmts[0]->stmts);
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $env = build_env($stmt->stmts);
        }
    }
} catch (PhpParser\Error $e) {
    echo 'Parse Error: ', $e->getMessage();
}
