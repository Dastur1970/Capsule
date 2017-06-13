<?php

use Capsule\Capsule;
use Capsule\CapsuleInterface;
use Capsule\Exceptions\CapsuleException;
use Capsule\Exceptions\NotFoundException;

use Capsule\Tests\Service;
use Capsule\Tests\ServiceTwo;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

class CapsuleTest extends PHPUnit_Framework_Testcase
{
    public function testCapsuleInstance()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $this->assertEquals($capsule, Capsule::getInstance());
        $capsule = new Capsule();
        $this->assertNotEquals($capsule, Capsule::getInstance());
    }

    /**
     * @expectedException \Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not retrieve non-existant instance nonexistant from the container.
     */
    public function testValueIsNotSet()
    {
        $capsule = new Capsule();
        $capsule->get('nonexistant');
        $this->expectException(CapsuleException::class);
    }

    public function testResolvingWithNamespace()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $service = $capsule->get(Service::class);
        $this->assertInstanceOf(Service::class, $service);
    }

    public function testResolvingWithName()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $service = $capsule->get('service');
        $this->assertInstanceOf(Service::class, $service);
    }

    public function testOnlyResolvesSingletonOnce()
    {
        $capsule = new Capsule();
        $capsule->singleton('service', Service::class, function($c) {
            return new Service();
        });
        $serviceOne = $capsule->get('service');
        $serviceOne->value = 'tester';
        $this->assertInstanceOf(Service::class, $serviceOne);

        $serviceTwo = $capsule->get('service');
        $this->assertInstanceOf(Service::class, $serviceTwo);

        $this->assertEquals($serviceOne, $serviceTwo);
    }

    public function testFactoryStaysAsCallable()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $serviceOne = $capsule->get('service');
        $serviceOne->value = 'test';
        $this->assertInstanceOf(Service::class, $serviceOne);

        $serviceTwo = $capsule->get('service');
        $this->assertInstanceOf(Service::class, $serviceTwo);

        $this->assertNotEquals($serviceOne, $serviceTwo);
    }

    public function testSetAsFactory()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $capsule->singleton('service2', ServiceTwo::class, function($c) {
            return new ServiceTwo();
        });
        $this->assertTrue($capsule->isFactory('service'));
        $this->assertFalse($capsule->isFactory('service2'));
    }

    public function testSetAsSingleton()
    {
        $capsule = new Capsule();
        $capsule->singleton('service', Service::class, function($c) {
            return new Service();
        });
        $capsule->bind('service2', ServiceTwo::class, function($c) {
            return new ServiceTwo();
        });
        $this->assertTrue($capsule->isSingleton('service'));
        $this->assertFalse($capsule->isSingleton('service2'));
    }

    public function testNamespaceIsSet()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $this->assertTrue($capsule->hasNamespace(Service::class));
        $this->assertFalse($capsule->hasNamespace(ServiceTwo::class));
    }

    public function testPassesCapsule()
    {
        $capsule = new Capsule();
        $capsule->bind('capsule', Capsule::class, function($c) {
            return $c;
        });
        $this->assertSame($capsule, $capsule->get('capsule'));
    }

    public function testResolvesSingleton()
    {
        $capsule = new Capsule();
        $capsule->singleton('service', Service::class, function($c) {
            return new Service();
        });
        $this->assertFalse($capsule->isResolved('service'));
        $capsule->get('service');
        $this->assertTrue($capsule->isResolved('service'));
    }

    /**
     * @expectedException \Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage The singleton service has already been resolved!
     */
    public function testBindingResolvedSingleton()
    {
        $capsule = new Capsule();
        $capsule->singleton('service', Service::class, function($c) {
            return new Service();
        });
        $this->assertFalse($capsule->isResolved('service'));
        $capsule->get('service');
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
    }

    public function testBindingisBound()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $this->assertTrue($capsule->has('service'));
    }

    /**
     * @expectedException \Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not bind to non-existant class Capsule\Capsiale
     */
    public function testBindingNonExistantClass()
    {
        $capsule = new Capsule();
        // Typo (Capsiale)
        $capsule->bind('service', \Capsule\Capsiale::class, function($c) {
            return new Service();
        });
    }

    public function testBindingStringAsClass()
    {
        $capsule = new Capsule();
        $capsule->bind('service', '\\Capsule\\Tests\\Service', function($c) {
            return new Service();
        });
        $this->assertTrue($capsule->hasNamespace(Service::class));
    }

    /**
     * @expectedException \Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not bind to non-existant class \Capsule\Capsiale
     */
    public function testBindingNonExistantStringAsClass()
    {
        $capsule = new Capsule();
        $capsule->bind('service', '\\Capsule\\Capsiale', function($c) {
            return new Service();
        });
    }

    /**
     * @expectedException \Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Could not bind service to the container, the given value is not an array of primitives or a callable.
     */
    public function testBindingWithoutThirdParameterCallableOrArray()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, 12);
    }

    public function testBindingReturnsCapsule()
    {
        $capsule = new Capsule();
        $sameCapsule = $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $this->assertEquals($capsule, $sameCapsule);
    }

    public function testClassesArePsrCompliant()
    {
        $capsule = new Capsule();
        $capsuleException = new CapsuleException();
        $notFoundException = new NotFoundException();

        $this->assertInstanceOf(ContainerInterface::class, $capsule);
        $this->assertInstanceOf(ContainerExceptionInterface::class, $capsuleException);
        $this->assertInstanceOf(NotFoundExceptionInterface::class, $notFoundException);
    }
}
