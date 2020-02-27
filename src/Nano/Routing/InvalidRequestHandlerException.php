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

namespace Nano\Routing;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown if an invalid request handler is provided.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidRequestHandlerException extends \InvalidArgumentException implements NanoExceptionInterface
{

}