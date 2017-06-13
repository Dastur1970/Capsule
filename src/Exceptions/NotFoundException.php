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

namespace Capsule\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * A exception that is thrown while trying to build a class.
 *
 * @category Exceptions
 * @package  Capsule
 * @author   Dastur1970 <dastur1970@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/Dastur1970
 * @see      http://php.net/manual/en/language.exceptions.php
 */
class NotFoundException extends CapsuleException implements
    NotFoundExceptionInterface
{
    //
}
