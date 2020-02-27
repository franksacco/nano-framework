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

/**
 * Implementation of LoggerInterface that prints logs with a simple HTML markup.
 *
 * Logger can be configured through the `log` key in application settings.
 * Available options for this class are:
 *  - `levels`: log levels that are considered for logging; by default all
 *   levels are logged.
 *  - `time_format`: format for the current timestamp; default: 'Y-m-d H:i:s.u'.
 *
 * @package Nano\Log
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class HtmlLogger extends AbstractLogger
{
    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->hasToBeLogged($level)) {
            echo <<<HTML
<div style="padding:6px;margin:6px 0;font-family:monospace;font-size:13px;background:rgba(0,0,0,.1);">
    [{$this->getTime()}] [<b>{strtoupper($level)}</b>] {$this->interpolate($message, $context)}
</div>
HTML;
        }
    }
}