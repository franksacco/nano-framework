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
 * Exception thrown if an argument does not match with the expected value.
 *
 * @package Nano\Config
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class UnexpectedValueException extends \UnexpectedValueException implements NanoExceptionInterface
{

}