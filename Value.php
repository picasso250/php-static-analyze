<?php

class Value
{
    public $type;
    // public $isAny = true;
    // public $values = [];

    public function __construct()
    {
        $this->type = Type::createUnknown();
    }
}
