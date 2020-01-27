<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2019 Francesco Saccani
 * @version   1.0
 */

namespace Nano\Model\Exception;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown when a property not exists or is not accessible.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class NotDefinedPropertyException extends \InvalidArgumentException implements NanoExceptionInterface
{

}
