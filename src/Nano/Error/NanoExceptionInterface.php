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

namespace Nano\Error;

use Throwable;

/**
 * Marker interface for framework exceptions.
 *
 * @package Nano\Error
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface NanoExceptionInterface extends Throwable
{

}
