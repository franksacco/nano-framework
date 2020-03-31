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

namespace Nano\Config;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown when an invalid prefix is provided.
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class InvalidPrefixException extends \RuntimeException implements NanoExceptionInterface
{

}