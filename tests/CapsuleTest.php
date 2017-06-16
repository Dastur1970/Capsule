 <?php

use Dastur\Capsule\Capsule;
use Dastur\Capsule\CapsuleInterface;
use Dastur\Capsule\Exceptions\CapsuleException;
use Dastur\Capsule\Exceptions\NotFoundException;

use Dastur\Capsule\Tests\Service;
use Dastur\Capsule\Tests\ServiceTwo;
use Dastur\Capsule\Tests\ServiceThree;
use Dastur\Capsule\Tests\NoConstructor;
use Dastur\Capsule\Tests\PrivateConstructor;
use Dastur\Capsule\Tests\PrimitiveConstructor;
use Dastur\Capsule\Tests\ConstructorWithClasses;
use Dastur\Capsule\Tests\ConstructorWithDefaults;

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
     * @expectedException \Dastur\Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not retrieve non-existant instance 'fake' from the container.
     */
    public function testValueIsNotSet()
    {
        $capsule = new Capsule();
        $capsule->get('fake');
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
     * @expectedException \Dastur\Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage The singleton 'service' has already been resolved.
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
     * @expectedException \Dastur\Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not bind to non-existant class 'Capsiale'.
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
        $capsule->bind('service', '\\Dastur\\Capsule\\Tests\\Service', function($c) {
            return new Service();
        });
        $this->assertTrue($capsule->hasNamespace(Service::class));
    }

    /**
     * @expectedException \Dastur\Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not bind to non-existant class 'Capsiale'.
     */
    public function testBindingNonExistantStringAsClass()
    {
        $capsule = new Capsule();
        $capsule->bind('service', '\\Dastur\\Capsule\\Capsiale', function($c) {
            return new Service();
        });
    }

    /**
     * @expectedException \Dastur\Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Could not bind 'service' to the container, the given value is not an array of primitives or a callable.
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

    /*
    |------------------------------------------
    | All the tests for the making an objects
    | (Including Build and getBuildParameters)
    |------------------------------------------
    */

    public function testMakeReturnsSingleton()
    {
        $capsule = new Capsule();
        $service = new Service();
        $service->value = 'test';
        $capsule->singleton('service', Service::class, function($c) use ($service) {
            return $service;
        });
        $this->assertEquals($service, $capsule->make(Service::class));
    }

    /**
     * @expectedException \Dastur\Capsule\Exceptions\ClassBuildingException
     * @expectedExceptionMessage Cannot make non-existant class 'Tester'.
     */
    public function testMakeWithNonExistantNamespace()
    {
        $capsule = new Capsule();
        $capsule->make(Tester::class);
    }

    /**
     * @expectedException \Dastur\Capsule\Exceptions\ClassBuildingException
     * @expectedExceptionMessage Can not build the class 'PrivateConstructor' as it is not instantiable.
     */
    public function testBuildWithPrivateConstructor()
    {
        $capsule = new Capsule();
        $capsule->make(PrivateConstructor::class);
    }

    public function testBuildWithNoConstructor()
    {
        $capsule = new Capsule();
        $noConstructor = $capsule->make(NoConstructor::class);
        $this->assertInstanceOf(NoConstructor::class, $noConstructor);
    }

    public function testBuildingWithPrimitives()
    {
        $capsule = new Capsule();
        $pConstructor = $capsule->make(
            PrimitiveConstructor::class,
            [
                'array' => ['test', 0, ['tester', false]],
                'int' => 12,
                'string' => 'cats',
                'boolean' => true
            ]
        );
        $this->assertInstanceOf(PrimitiveConstructor::class, $pConstructor);
        $this->assertEquals($pConstructor->array, ['test', 0, ['tester', false]]);
        $this->assertEquals($pConstructor->int, 12);
        $this->assertEquals($pConstructor->string, 'cats');
        $this->assertEquals($pConstructor->boolean, true);
    }

    public function testBuildingWithClasses()
    {
        $capsule = new Capsule();
        $withClasses = $capsule->make(ConstructorWithClasses::class);
        $this->assertInstanceOf(ConstructorWithClasses::class, $withClasses);
        $this->assertInstanceOf(Service::class, $withClasses->service1);
        $this->assertInstanceOf(ServiceTwo::class, $withClasses->service2);
        $this->assertInstanceOf(ServiceThree::class, $withClasses->service3);
    }

    public function testBuildingWithDefaults()
    {
        $capsule = new Capsule();
        $withDefaults = $capsule->make(ConstructorWithDefaults::class);
        $this->assertInstanceOf(ConstructorWithDefaults::class, $withDefaults);
        $this->assertEquals($withDefaults->array, ['test', 'test']);
        $this->assertEquals($withDefaults->string, 'test');
        $this->assertEquals($withDefaults->int, 12);
    }

    /**
     * @expectedException \Dastur\Capsule\Exceptions\ClassBuildingException
     * @expectedExceptionMessage Can not build class 'PrimitiveConstructor' because parameter 'array' can not be resolved.
     */
    public function testBuildingUnresolvableClass()
    {
        $capsule = new Capsule();
        $capsule->make(PrimitiveConstructor::class);
    }

    /**
     * @expectedException \Dastur\Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not destroy 'Fake' because it does not exist.
     */
    public function testDestroyWithFakeName()
    {
        $capsule = new Capsule();
        $capsule->destroy('Fake');
    }

    /**
     * @expectedException \Dastur\Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not retrieve non-existant instance 'service' from the container.
     */
    public function testDestroyingInstance()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $capsule->destroy('service');
        $capsule->get('service');
    }

    /**
     * @expectedException \Dastur\Capsule\Exceptions\CapsuleException
     * @expectedExceptionMessage Can not retrieve non-existant instance 'Service' from the container.
     */
    public function testDestroyingInstanceWithNamespace()
    {
        $capsule = new Capsule();
        $capsule->bind('service', Service::class, function($c) {
            return new Service();
        });
        $capsule->destroy(Service::class);
        $capsule->get(Service::class);
    }
}
