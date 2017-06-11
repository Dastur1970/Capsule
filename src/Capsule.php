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

namespace Capsule;

use Capsule\Exceptions\CapsuleException;

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
class Capsule implements CapsuleInterface
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
     */
    public function get($name)
    {
        // Check if the value has been set. If not, throw an exception.
        if (isset($this->values[$name])) {
            throw new CapsuleException(
                'Tried to get an instance from the '
                . 'container that has not been set'
            );
        }

        $name = isset($this->namespaces[$name])
            ? $this->namespaces[$name] : $name;

        // If it has already been resolved, return the
        // Value without requiring instantiation.
        if ($this->isResolved($name)) {
            return $this->values[$name];
        }

        // If what the developer is resolving is not a singleton
        // Return a newly instantiated object.
        if ($this->factories($name)) {
            return $this->values[$name]($this);
        }

        $callable = $this->values[$name];
        $val = $this->values[$name] = $callable($this);
        $this->resolved[$name] = true;
        return $val;
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
        // If it is a singleton, check if it has already been resolved
        // If not, put it in the resolved array with a value of false.
        if ($singleton) {
            if ($this->isResolved($name)) {
                throw new CapsuleException(
                    'The singleton ' . $name . ' has already been resolved!'
                );
            }
            $this->resolved[$name] = false;
        }
        // Check if the namespace given is an actual
        // Class. If not, throw an error.
        if (! is_class($namespace)) {
            throw new CapsuleException(
                'Can not bind to non-existant class ' . $namespace
            );
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
                'Could not bind ' . $name
                . ' to the container, the given value is not an'
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
        $this->bind($name, $namespace, $value, true);
    }

    /**
     * Make a class by resolving it's dependencies from the container.
     *
     * @param mixed $namespace  The class constant for what is being made.
     * @param array $parameters An array of primitive parameters.
     *
     * @return mixed The class that has just been resolved.
     */
    public function make($namespace, array $parameters = [])
    {
        //
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
     * @param string $singleton The name of the singleton that you are checking.
     *
     * @return bool Whether or not it has already been resolved
     */
    public function isResolved($singleton)
    {
        return $this->resolved[$singleton];
    }
}
