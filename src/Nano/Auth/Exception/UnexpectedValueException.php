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
 * Exception thrown if a value does not match with a set of values or defined rules.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class UnexpectedValueException extends \UnexpectedValueException implements NanoExceptionInterface
{

}
