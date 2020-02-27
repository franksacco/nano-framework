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

namespace Nano\Log;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown by the logging engine.
 *
 * @package Nano\Log
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class LoggerException extends \RuntimeException implements NanoExceptionInterface
{

}