<?php
function build_env($stmts)
{
    $env = new Environment;
    foreach ($stmts as $stmt) {
        // print_r($stmt);
        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            // fix: list(a, b) = $a;
            $env->addExpr($stmt->var->name, $stmt->expr);
        } elseif (Operator::isArithmeticAssign(get_class($stmt))) {
            $env->addType($stmt->var->name, 'Scalar_DNumber');
        } elseif (Operator::isIncrOrDecr(get_class($stmt))) {
            $env->addType($stmt->var->name, 'Scalar_DNumber');
        } else {
            throw new Exception("unkown type ".get_class($stmt), 1);
        }
    }
    return $env;
}
