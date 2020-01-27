<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2019 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Auth\Exception;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown when an error occur during user authentication.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class NotAuthenticatedException extends \Exception implements NanoExceptionInterface
{

}
