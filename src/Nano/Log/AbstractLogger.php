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

use DateTime;
use Nano\Config\ConfigurationInterface;
use Psr\Log\AbstractLogger as Psr3AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Basic logger implementation.
 *
 * This class offers an implementation of placeholder interpolation that can be
 * done in a log message with a specified context array.
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
abstract class AbstractLogger extends Psr3AbstractLogger
{
    const ALL_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG
    ];

    /**
     * @var array
     */
    private $levels;

    /**
     * @var string
     */
    private $timeFormat;

    /**
     * Initialize the logger.
     *
     * @param ConfigurationInterface $config The application settings.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->levels     = (array) $config->get('log.levels', self::ALL_LEVELS);
        $this->timeFormat = $config->get('log.time_format', 'Y-m-d H:i:s.u');
    }

    /**
     * Set the levels that are considered for logging.
     *
     * @param array $levels
     */
    public function setLevels(array $levels)
    {
        $this->levels = $levels;
    }

    /**
     * Set the format for the current timestamp.
     *
     * @param string $timeFormat
     */
    public function setTimeFormat(string $timeFormat)
    {
        $this->timeFormat = $timeFormat;
    }

    /**
     * Check if the provided log level has to be logged.
     *
     * @param string $level The log level.
     * @return bool
     */
    protected function hasToBeLogged(string $level): bool
    {
        return in_array($level, $this->levels, true);
    }

    /**
     * Retrieve the current timestamp.
     *
     * @return string
     */
    protected function getTime(): string
    {
        $now = DateTime::createFromFormat('U.u', (string) microtime(true));
        return $now->format($this->timeFormat);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message The message with brace-delimited placeholder
     *   names.
     * @param array $context The context array of placeholder
     *   `names` => `replacement` values.
     * @return string Returns the interpolated message.
     */
    protected function interpolate($message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }
}
