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

namespace Nano\Model\Exception;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown when an error occur during a query execution in the Model package.
 *
 * @package Nano\Model
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class ModelExecutionException extends \RuntimeException implements NanoExceptionInterface
{

}
