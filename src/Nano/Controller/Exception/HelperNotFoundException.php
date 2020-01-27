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

namespace Nano\Controller\Exception;

use Nano\Error\NanoExceptionInterface;

/**
 * Exception thrown when a component does not exist.
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class HelperNotFoundException extends \RuntimeException implements NanoExceptionInterface
{

}
