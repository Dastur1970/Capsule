<?php

namespace Capsule\Tests;

class ConstructorWithClasses
{
    public $service1;
    public $service2;
    public $service3;

    public function __construct(Service $service1, ServiceTwo $service2, ServiceThree $service3)
    {
        $this->service1 = $service1;
        $this->service2 = $service2;
        $this->service3 = $service3;
    }
}
