<?php
/**
 * PHP version 5.6
 *
 * @category Capsule
 * @package  Capsule
 * @author   Dastur1970 <dastur1970@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/Dastur1970
 */

namespace Dastur\Capsule;

use Dastur\Capsule\Exceptions\CapsuleException;
use Dastur\Capsule\Exceptions\ClassBuildingException;
use Dastur\Capsule\Exceptions\NotFoundException;

use ReflectionClass;
use ArrayAccess;

/**
 * A php dependency injection container.
 *
 * @category Capsule
 * @package  Capsule
 * @author   Dastur1970 <dastur1970@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/Dastur1970
 * @see      Capsule\CapsuleInterface
 */
class Capsule implements CapsuleInterface, ArrayAccess
{
    /**
     * The container instance.
     *
     * @var Capsule\Capsuke
     */
    protected static $instance;

    /**
     * All of the values bound to the container.
     *
     * [
     *    'name' => Callable or Object
     * ]
     *
     * @var array
     */
    protected $values = [];

    /**
     * Every factory instance (non-singleton).
     *
     * [
     *     'name' => boolean
     * ]
     *
     * @var array
     */
    protected $factories = [];

    /**
     * Whether or not a singleton has been resolved.
     *
     * [
     *     'name' => boolean
     * ]
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * The namespaces of every object bound to the container.
     *
     * [
     *     'name' => class constant
     * ]
     *
     * @var array
     */
    protected $namespaces = [];

    /**
     * Create a a new Capsule.
     */
    public function __construct()
    {
        if (! isset(static::$instance)) {
            static::$instance = $this;
        }
    }

    /**
     * Get an instance from the container.
     *
     * @param mixed $name The name of what is being resolved.
     *
     * @return mixed
     *
     * @throws Dastur\Capsule\Exceptions\NotFoundException
     */
    public function get($name)
    {
        // If the developer is using a namespace to retrieve
        // From the container, get the namespaces defined name.
        $name = $this->convertNamespace($name);

        // Check if the value has been set. If not, throw an exception.
        if (! $this->has($name)) {
            throw new NotFoundException(
                'Can not retrieve non-existant instance \''
                . $this->getClassName($name) . '\' from the container.'
            );
        }

        // If what the developer is resolving is not a singleton
        // Return a newly instantiated object.
        if ($this->isFactory($name)) {
            return $this->values[$name]($this);
        }

        // If it has already been resolved, return the
        // Value without requiring instantiation.
        if ($this->isResolved($name)) {
            return $this->values[$name];
        }

        $callable = $this->values[$name];
        $obj = $this->values[$name] = $callable($this);
        $this->resolved[$name] = true;
        return $obj;
    }

    /**
     * Bind an instance into the container.
     *
     * @param string $name      The name of what is being set.
     * @param mixed  $namespace The class constant of the object being bound.
     * @param mixed  $value     The value of what is being set.
     * @param bool   $singleton Whether or not it should be
     *                          instantiated multiple times.
     *
     * @return Capsule\Capsule The container instance.
     *
     * @throws Capsule\Exceptions\CapsuleException
     */
    public function bind($name, $namespace, $value = [], $singleton = false)
    {
        // If this is attempting to override a resolved singleton,
        // Throwna CapsuleException
        if ($this->isResolved($name)) {
            throw new CapsuleException(
                'The singleton \'' . $name . '\' has already been resolved.'
            );
        }

        // If it is a singleton set resolved to false.
        if ($singleton) {
            $this->resolved[$name] = false;
        }

        // Check if the namespace given is an actual
        // Class. If not, throw an error.
        if (! class_exists($namespace)) {
            throw new CapsuleException(
                'Can not bind to non-existant class \''
                . $this->getClassName($namespace) . '\'.'
            );
        }

        // If the provided namespace was a string, then trim the left side.
        if (is_string($namespace)) {
            $namespace = ltrim($namespace, '\\');
        }

        // Whether or not it's a factory (The opposite of singleton's value)
        $this->factories[$name] = !$singleton;

        // Put the namespace into the namespaces array (For when it must be
        // Resolved as a parameter)
        $this->namespaces[$namespace] = $name;

        // If the value is an array, that means the developer would like to
        // Make the object at the given namespace and put it into the
        // Container under the given name. The reason it's checking if
        // It is an array is because the user could set an array of primitives
        // That are required to instantiate the object.
        if (is_array($value)) {
            $value = function ($c) use ($namespace, $value) {
                $c->make($namespace, $value);
            };
        } elseif (!is_callable($value)) {
            // Otherwise, if it's not callable, the throw an error.
            throw new CapsuleException(
                'Could not bind \'' . $name
                . '\' to the container, the given value is not an'
                . ' array of primitives or a callable.'
            );
        }

        // Bind the callable to the container.
        $this->values[$name] = $value;

        // Return the container instance to allow for method chains.
        return $this;
    }

    /**
     * Bind a singleton into the container.
     *
     * @param mixed $name      The name of what is being set.
     * @param mixed $namespace The class constant of the object being bound.
     * @param mixed $value     The value of what is being set.
     *
     * @return Capsule\Capsule The container instance.
     */
    public function singleton($name, $namespace, $value = [])
    {
        return $this->bind($name, $namespace, $value, true);
    }

    /**
     * Bind an existant instance into the container.
     *
     * @param mixed $name      The name of what is being set.
     * @param mixed $namespace The class constant of the object being set.
     * @param mixed $value     The class being passed in.
     *
     * @return Capsule\Capsule The container instance.
     *
     * @throws Capsule\Exceptions\CapsuleException
     */
    public function instance($name, $namespace, $value = null)
    {
        // If value is null, it means it hasn't been set.
        // That means they are either not using an alias or they
        // Are not using a namespace.
        if (is_null($value)) {
            $value = $namespace;
            // If $name is an existant class, they are not using an alias,
            // They are binding to a namespace.
            if (class_exists($name)) {
                // Set it to itself because the alias we will be using
                // Is also it's namespace.
                $this->namespaces[$name] = $name;
            }
        } else {
            $this->namespaces[$namespace] = $name;
        }
        $this->values[$name] = $value;
        // All instances have already been resolved because they
        // Are not using closures to enter the capsule.
        $this->resolved[$name] = true;
        $this->factories[$name] = false;
        return $this;
    }

    /**
     * Make a class by resolving it's dependencies from the container.
     *
     * @param mixed $namespace  The class constant for what is being made.
     * @param array $parameters An array of primitive parameters.
     *
     * @return mixed The class that has just been resolved.
     *
     * @throws Capsule\Exceptions\ClassBuildingException
     */
    public function make($namespace, array $parameters = [])
    {
        // If it already exists as a singleton in
        // The container, return it
        if ($this->isSingleton($this->convertNamespace($namespace))) {
            return $this->get($namespace);
        }

        // If the developer is trying to make a class that
        // Doesn't exist, throw a CapsuleException
        if (! class_exists($namespace)) {
            throw new ClassBuildingException(
                'Cannot make non-existant class \''
                . $this->getClassName($namespace) . '\'.'
            );
        }

        // Build the instance at the given namespace.
        $obj = $this->build($namespace, $parameters);
        return $obj;
    }

    /**
     * Build a new instance using the container and reflection.
     *
     * @param mixed $namespace  The namespace of the class being built.
     * @param array $parameters The primitive parameters required
     *                          to build the instance.
     *
     * @return mixed The built instance.
     *
     * @throws Capsule\Exceptions\ClassBuildingException
     */
    protected function build($namespace, array $parameters = [])
    {
        // Create a ReflectionClass instance for the given namespace.
        $reflector = new ReflectionClass($namespace);

        // If the reflector is not instantiable, throw a ClassBuildingException.
        if (! $reflector->isInstantiable()) {
            throw new ClassBuildingException(
                'Can not build the class \'' . $this->getClassName($namespace)
                . '\' as it is not instantiable.'
            );
        }
        // Get the classes constructer.
        $constructor = $reflector->getConstructor();

        // If the constructer is null, it means the class has no
        // Constructer. Therefore we can go ahead and create the class
        // Without needing to build anything.
        if (is_null($constructor)) {
            return new $namespace;
        }

        // Get the parameters for the build.
        $parameters = $this->getBuildParameters(
            $constructor->getParameters(),
            $parameters
        );

        return $reflector->newInstanceArgs($parameters);
    }

    /**
     * Get the build parameters.
     *
     * @param array $parameters The constructor parameters.
     * @param array $primitives The primitive overrides.
     *
     * @return array The array of parameters.
     *
     * @throws Dastur\Capsule\Exceptions\ClassBuildingException
     */
    protected function getBuildParameters($parameters, $primitives = [])
    {
        $values = [];
        foreach ($parameters as $parameter) {
            // If the primitives array has a key with the same
            // Name as one of the constructer parameters,
            // Then use that instead. Although the variable is
            // Called primitives, you may override classes as well
            $paramName = $parameter->getName();
            if (isset($primitives[$parameter->getName()])) {
                $values[] = $primitives[$paramName];
                continue;
            }
            // Try and get the class from the container.
            if (! is_null($parameter->getClass())) {
                $values[] = $this->make($parameter->getClass()->getName());
                continue;
            }
            // If nothing else worked, try to use a parameters default value.
            if ($parameter->isDefaultValueAvailable()) {
                $values[] = $parameter->getDefaultValue();
                continue;
            }
            // Throw an error because the given
            // Constructer parameter can not be resolved.
            throw new ClassBuildingException(
                'Can not build class \''
                . $parameter->getDeclaringClass()->getShortName()
                . '\' because parameter \'' . $paramName
                . '\' can not be resolved.'
            );
        }
        return $values;
    }

    /**
     * Destroy a instance binded to the container.
     *
     * @param mixed $name Either the name or namespace
     *                    of the class being destroyed.
     *
     * @return void
     *
     * @throws Dastur\Capsule\Exceptions\CapsuleException
     */
    public function destroy($name)
    {
        // Convert the potential namespace into a name.
        $name = $this->convertNamespace($name);

        // Throw an exception if the name does exist within the capsule.
        if (! $this->has($name)) {
            throw new CapsuleException(
                "Can not destroy '" . $name . "' because it does not exist."
            );
        }

        // Unset every possible trace of it from the container.
        if ($this->isSingleton($name)) {
            unset($this->resolved[$name]);
        }
        unset($this->factories[$name]);
        unset($this->values[$name]);
        unset($this->namespaces[array_search($name, $this->namespaces)]);
    }

    /**
     * Get the container instance.
     *
     * @return Capsule\Capsule The container instance.
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Whether or not a singleton has been resolved.
     *
     * @param mixed $name The name or class of the
     *                    singleton that you are checking.
     *
     * @return bool Whether or not it has already been resolved
     */
    public function isResolved($name)
    {
        if ($this->isSingleton($name)) {
            return $this->resolved[$name];
        }
        return false;
    }

    /**
     * Determines whether or not a value has been bound to the container.
     *
     * @param mixed $name The name or class of the
     *                    value that you are checking.
     *
     * @return bool Whether or not it has been bound.
     */
    public function has($name)
    {
        $name = $this->convertNamespace($name);
        return isset($this->values[$name]);
    }

    /**
     * Determines whether or not a factory has been bound to the container.
     *
     * @param mixed $name The name or class of the
     *                    factory that you are checking.
     *
     * @return bool Whether or not the factory has been bound.
     */
    public function isFactory($name)
    {
        return $this->factories[$name];
    }

    /**
     * Determines whether or not a singleton has been bound to the container.
     *
     * @param mixed $name The name or class of the
     *                    singleton that you are checking.
     *
     * @return bool Whether or not the singleton has been bound.
     */
    public function isSingleton($name)
    {
        // Because to be in the resolved array, you must be
        // A singleton, just check this array to see if the key exists.
        return isset($this->resolved[$name]);
    }

    /**
     * Determines whether or not a namespace has been bound to the container.
     *
     * @param mixed $namespace The namespace that you are checking.
     *
     * @return bool Whether or not the namespace has been bound.
     */
    public function hasNamespace($namespace)
    {
        return isset($this->namespaces[$namespace]);
    }

    /**
     * Converts a namespace to the name bound to the container.
     *
     * @param mixed $namespace The namespace that you are converting.
     *
     * @return string
     */
    protected function convertNamespace($namespace)
    {
        // If the container has the specified
        // Namespace bound to it return that.
        if ($this->hasNamespace($namespace)) {
            return $this->namespaces[$namespace];
        }
        // Otherwise, assume that the name given is
        // The name of the service.
        return $namespace;
    }

    /**
     * Get a classes name from a class constant or string.
     *
     * @param mixed $namespace The namespace that you are retrieving
     *                         the name from.
     *
     * @return string
     */
    protected function getClassName($namespace)
    {
        $parts = explode('\\', $namespace);
        return $parts[count($parts) - 1];
    }

    /*
     |--------------------------------------
     | Magic Methods and Array
     |--------------------------------------
     */

     /**
      * Get an instance from the container.
      *
      * @param mixed $offset The name of what is being resolved.
      *
      * @return mixed
      *
      * @throws Dastur\Capsule\Exceptions\NotFoundException
      */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Get developers to use bind instead of offsetSet
     *
     * @param mixed $offset The name of what is being set.
     * @param mixed $value  The value of what is being set.
     *
     * @return void
     *
     * @throws Dastur\Capsule\Exceptions\CapsuleException
     */
    public function offsetSet($offset, $value)
    {
        throw new CapsuleException(
            'To bind to the container, use the the bind method instead.'
        );
    }

    /**
     * Determines whether or not a value has been bound to the container.
     *
     * @param mixed $offset The name or class of the
     *                      value that you are checking.
     *
     * @return bool Whether or not it has been bound.
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Destroy a instance binded to the container.
     *
     * @param mixed $offset Either the name or namespace
     *                      of the class being destroyed.
     *
     * @return void
     *
     * @throws Dastur\Capsule\Exceptions\CapsuleException
     */
    public function offsetUnset($offset)
    {
        $this->destroy($offset);
    }

    /**
     * Get an instance from the container.
     *
     * @param string $name The name of what is being resolved.
     *
     * @return mixed
     *
     * @throws Dastur\Capsule\Exceptions\NotFoundException
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Get developers to use bind instead of __get
     *
     * @param string $name  The name of what is being set.
     * @param mixed  $value The value of what is being set.
     *
     * @return void
     *
     * @throws Dastur\Capsule\Exceptions\CapsuleException
     */
    public function __set($name, $value)
    {
        throw new CapsuleException(
            'To bind to the container, use the the bind method instead.'
        );
    }

    /**
     * Determines whether or not a value has been bound to the container.
     *
     * @param string $name The name or class of the
     *                     value that you are checking.
     *
     * @return bool Whether or not it has been bound.
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Destroy a instance binded to the container.
     *
     * @param string $name Either the name or namespace
     *                     of the class being destroyed.
     *
     * @return void
     *
     * @throws Dastur\Capsule\Exceptions\CapsuleException
     */
    public function __unset($name)
    {
        return $this->destroy($name);
    }
}
