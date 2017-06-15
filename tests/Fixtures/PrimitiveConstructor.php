<?php

namespace Capsule\Tests;

class PrimitiveConstructor
{
    public $array = [];
    public $int;
    public $string;
    public $boolean;

    public function __construct(array $array, $int, $string, $boolean)
    {
        $this->array = $array;
        $this->int = $int;
        $this->string = $string;
        $this->boolean = $boolean;
    }
}
