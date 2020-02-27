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

namespace Nano\Middleware;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown if a given middleware is not valid.
 *
 * @package Nano\Application
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidMiddlewareException extends \InvalidArgumentException implements NanoExceptionInterface
{

}