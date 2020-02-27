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

namespace Nano\Container;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown when an invalid DI container is provided.
 *
 * @package Nano\Container
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidContainerException extends \InvalidArgumentException implements NanoExceptionInterface
{

}