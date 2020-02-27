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

use Nano\Config\ConfigurationInterface;

/**
 * Implementation of LoggerInterface that prints logs in a single file.
 *
 * Logger can be configured through the `log` key in application settings.
 * Available options for this class are:
 *  - `levels`: log levels that are considered for logging; by default all
 *   levels are logged.
 *  - `time_format`: format for the current timestamp; default: 'Y-m-d H:i:s.u'.
 *  - `path`: path of the log file.
 *
 * @package Nano\Log
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class FileLogger extends AbstractLogger
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var resource
     */
    protected $file;

    /**
     * @inheritDoc
     */
    public function __construct(ConfigurationInterface $config)
    {
        parent::__construct($config);
        $this->path = $config->get('log.path');
    }

    /**
     * Set the path of the log file.
     *
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->hasToBeLogged($level)) {
            if ($this->file === null) {
                $this->file = $this->open();
            }

            $log = sprintf(
                '[%s] [%s] %s',
                $this->getTime(),
                strtoupper($level),
                $this->interpolate($message, $context)
            );
            flock($this->file, LOCK_EX);
            fwrite($this->file, $log . PHP_EOL);
            flock($this->file, LOCK_UN);
        }
    }

    /**
     * Open the file handle.
     *
     * @return resource Returns the file handle,
     */
    protected function open()
    {
        if (! is_string($this->path)) {
            throw new LoggerException('Path option is missing or not a string');
        }

        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 775, true)){
            throw new LoggerException('Unable to create the directory ' . $directory);
        }

        $file = fopen($this->path, 'a');
        if ($file === false) {
            throw new LoggerException('Error during file opening');
        }
        fwrite($file, PHP_EOL);
        return $file;
    }

    /**
     * Close the file handle.
     */
    protected function close()
    {
        fclose($this->file);
        $this->file = null;
    }

    /**
     * Close the file handle.
     */
    public function __destruct()
    {
        if ($this->file !== null) {
            $this->close();
        }
    }
}