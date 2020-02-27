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

namespace Nano\Session\Exception;

use RuntimeException;

/**
 * Exception thrown if a session ini option fails to be set.
 *
 * @package Nano\Session\Exception
 * @author Francesco Saccani <saccani.francesco@gmail.com>
 */
class ConfigurationException extends RuntimeException implements SessionExceptionInterface
{

}