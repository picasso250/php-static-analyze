<?php
function build_env($stmts)
{
    $table = [];
    foreach ($stmts as $stmt) {
        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            // fix: list(a, b) = $a;
            if (isset($table[$stmt->var->name])) {
                // echo "addExpr {$stmt->var->name}\n";
                $table[$stmt->var->name]->addExpr($stmt->expr);
            } else {
                // echo "createFromExpr {$stmt->var->name}\n";
                // echo $stmt->getAttribute('startLine'), "\n";
                $table[$stmt->var->name] = Value::createFromExpr($stmt->expr);
            }
        }
    }
    return $table;
}
