<?php

class Environment extends ArrayObject
{
    public function addType($name, $type)
    {
        if (isset($this[$name])) {
            $this[$name]->addType($expr);
        } else {
            $this[$name] = Type::createFromTypes([$type]);
        }
    }
    public function addExpr($name, $expr)
    {
        if (isset($this[$name])) {
            // echo "addExpr {$name}\n";
            $this[$name]->addExpr($expr);
        } else {
            // echo "createFromExpr {$name}\n";
            $this[$name] = Type::createFromExpr($expr);
        }
    }
}
