# Capsule

Capsule is a PHP dependency injection container.
It allows you to bind instances to the container so you can retrieve them when they are required.
If you would like to make sure none of your dependencies are hidden, you can use the automatic dependency injection provided by this package.

## Table of Contents

- [Install](#install)
- [Usage](#usage)
  - [Binding](#binding)
  - [Resolving](#resolving)
  - [Binding with Making](#binding-with-making-resolving)
  - [Destroying](#destroying)
- [Contribute](#contribute)
- [License](#license)

## Install

### Composer

Will add instructions here once I set it up on packagist.

## Usage

To start using capsule, create a new Capsule instance.

```php
$capsule = new Capsule();
```

### Binding

To bind a class to the container, use the bind method
```php
$capsule->bind(string $name, mixed $path, callable $value, boolean $singleton = false)
```

$name parameter is the bindings alias.
$path parameter is a class constant or string (that contains the full namespace).
This is used by the Capsule when it needs to resolve a classes dependencies.
$value parameter is a callable that returns the service that you are binding.
$singleton parameter is whether or not you want the instance to only be resolved once.

Example:

```php
$capsule->bind('service', Service::class, function() {
  return new Service();
});
```

By default, Capsule creates a new instance every time you get something from the container.
If you want something to only be resolved once, use the `singleton()` method.

```php
$capsule->singleton('service', Service::class, function() {
  return new Service();
});
```

### Resolving

When you need to get an instance from the container, use the get method

```php
$capsule->get(mixed $name)
```

$name parameter is the name or class constant for the class that is being resolved.
This method will only resolve classes that are stored in the Capsule.

Example:

```php
$capsule->get('service') or $capsule->get(Service::class)
```

Alternatively, you may use the following syntax:

 ```php
 $capsule->service or $capsule['service']
 ```

If you need to create a class that is not binded in the container, use the make method.

```php
$capsule->make(mixed $path, array $parameters = [])
```

$path is the class constant for the class you are resolving.
You may use a string for this parameter as well, but make sure it is formatted correctly or else an error will be thrown.
$parameters parameter is an array of overrides you would like to define.

For example:

Lets say you have the class `Service` that is dependant upon `AnotherService` and also requires a random string in its constructor.

```php
<?php
namespace Package\Service

class Service
{
  public $anotherService;
  public $string;
  
  public function __construct(AnotherService $anotherService, $string)
  {
    $this->anotherService = $anotherService;
    $this->string = $string;
  }
}
```

Now, using the make method, the Capsule can resolve this service for you.

```php
$service = $capsule->make(\Package\Service::class, ['string' => 'randomString')
```

Now, if you try to access the `$string` and `$anotherService` properties,
they will return `randomString` and a AnotherService instance respectively.

This is especially powerful when you have some important singletons bound into the container (ie. Router, Logger, etc...).
Capsule can resolve those parameters for you.

Example:

```php
<?php
namespace Package\Service

class Service
{
  public $router;
  public $logger;
  
  public function __construct(Router $router, Logger $logger)
  {
    $this->router = $router;
    $this->logger = $logger;
  }
}
```

```php
$capsule->make(\Package\Service::class)
```

### Binding with Making (Resolving)

If there is a class, and you simply want to bind it into the framework without any configuration you can do the following:

```php
$capsule->bind('service', Service::class)
```

If capsule sees this, it will bind `Service` into the container using the make method.
This is the equivalent of doing the following:

```php
$capsule->bind('service', Service::class, function($capsule) {
  return $capsule->make(Service::class);
});
```

If you need to specify overrides, you may specify a third parameter.

```php
$capsule->bind('service', Service::class, ['string' => 'random'])
```

### Destroying

If you need to destroy an instance in the container, you can do so using the destroy method.

```php
$capsule->destroy(mixed $name)
```

Example:

```php
$capsule->destroy('service') or $capsule->destroy(Service::class)
```

Alternatively, you can also use the following syntax:
```php
unset($capsule->service) or unset($capsule['service'])
```

## Contribute

This repository is accepting contributions. Check out the [Contributing](CONTRUBTING) guide.

## License

[MIT © Dastur1970](LICENSE)
