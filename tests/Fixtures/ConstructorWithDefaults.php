<?php

namespace Dastur\Capsule\Tests;

class ConstructorWithDefaults
{
    public $array;
    public $string;
    public $int;

    public function __construct(array $array = ['test', 'test'], $string = 'test', $int = 12)
    {
        $this->array = $array;
        $this->string = $string;
        $this->int = $int;
    }
}
