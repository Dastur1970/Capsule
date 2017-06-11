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

/**
 * The interface for the php dependency injection container.
 *
 * @category Capsule
 * @package  Capsule
 * @author   Dastur1970 <dastur1970@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/Dastur1970
 */
interface ContainerInterface
{
    /**
     * Get an instance from the container.
     *
     * @param mixed $name The name of what is being resolved.
     *
     * @return mixed
     */
    public function get($name);

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
    public function bind($name, $namespace, $value = [], $singleton = false);

    /**
     * Bind a singleton into the container.
     *
     * @param mixed $name      The name of what is being set.
     * @param mixed $namespace The class constant of the object being bound.
     * @param mixed $value     The value of what is being set.
     *
     * @return Capsule\Capsule The container instance.
     */
    public function singleton($name, $namespace, $value = []);

    /**
     * Make a class by resolving it's dependencies from the container.
     *
     * @param mixed $namespace  The class constant for what is being made.
     * @param array $parameters An array of primitive parameters.
     *
     * @return mixed The class that has just been resolved.
     */
    public function make($namespace, array $parameters = []);

    /**
     * Get the container instance.
     *
     * @return Capsule\Capsule The container instance.
     */
    public static function getInstance();

    /**
     * Whether or not a singleton has been resolved.
     *
     * @param string $singleton The name of the singleton that you are checking.
     *
     * @return bool Whether or not it has already been resolved
     */
    public function isResolved($singleton);
}
