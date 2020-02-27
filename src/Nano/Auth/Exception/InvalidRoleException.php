<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2020 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Auth\Exception;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown if a given role is not valid.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidRoleException extends \RuntimeException implements NanoExceptionInterface
{

}