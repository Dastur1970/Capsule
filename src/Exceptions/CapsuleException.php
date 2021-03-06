<?php
/**
 * PHP version 5.6
 *
 * @category Exceptions
 * @package  Capsule
 * @author   Dastur1970 <dastur1970@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/Dastur1970
 */

namespace Dastur\Capsule\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * A general exception thrown in the Capsule.
 *
 * @category Exceptions
 * @package  Capsule
 * @author   Dastur1970 <dastur1970@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/Dastur1970
 * @see      http://php.net/manual/en/language.exceptions.php
 */
class CapsuleException extends Exception implements ContainerExceptionInterface
{
    //
}
