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

namespace Nano\Session\Handler;

use Nano\Config\ConfigurationInterface;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Session handler based on file storage.
 *
 * This handler implements file locking to avoid server-side race
 * conditions, collision free ID generation and session ID validation
 * to avoid uninitialized session ID.
 *
 * File session handler can be configured through the `session.file` key in
 * application settings.
 * Available options for this class are:
 *  - "prefix": prefix for session file name; default: "sess_".
 *
 * The path of the current directory used to save session data is defined
 * according to the value of `session.save_path` ini directive value. If this
 * option is leaved empty, system default temporary folder is used.
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class FileSessionHandler implements SessionHandlerInterface,
                                    SessionIdInterface,
                                    SessionUpdateTimestampHandlerInterface
{
    /**
     * @var string
     */
    private $savePath = '';

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $lastCreatedId;

    /**
     * Initialize the file session handler.
     *
     * @param ConfigurationInterface $config The application settings.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->prefix = $config->get('session.file.prefix', 'sess_');
    }

    /**
     * @inheritDoc
     */
    public function open($savePath, $name)
    {
        $path = explode(';', $savePath);
        $path = rtrim(end($path), DIRECTORY_SEPARATOR);
        $this->savePath = $path ?: sys_get_temp_dir();
        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($sessionId)
    {
        $file = $this->savePath . DIRECTORY_SEPARATOR . $this->prefix . $sessionId;
        $expiration = time() - (int) ini_get('session.gc_maxlifetime');
        if (is_file($file) && filemtime($file) > $expiration) {
            $size = filesize($file);
            if ($size && $handle = fopen($file, 'r')) {
                if (flock($handle, LOCK_EX)) {
                    $data = fread($handle, $size);
                    fclose($handle);
                    return $data;
                }
            }
        }
        return '';
    }

    /**
     * @inheritDoc
     */
    public function write($sessionId, $sessionData)
    {
        $file = $this->savePath . DIRECTORY_SEPARATOR . $this->prefix . $sessionId;
        if ($handle = fopen($file, 'w')) {

            $result = fwrite($handle, $sessionData);
            if ($result !== false) {
                $result = flock($handle, LOCK_UN);
            }
            fclose($handle);
            return $result;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroy($sessionId)
    {
        $file = $this->savePath . DIRECTORY_SEPARATOR . $this->prefix . $sessionId;
        return file_exists($file) ? unlink($file) : true;
    }

    /**
     * @inheritDoc
     */
    public function gc($maxlifetime)
    {
        // We cannot trust $this->savePath value because is
        // empty until the session is started.
        $path = explode(';', ini_get('session.save_path'));
        $path = rtrim(end($path), DIRECTORY_SEPARATOR) ?: sys_get_temp_dir();

        $pattern    = $path . DIRECTORY_SEPARATOR . $this->prefix . '*';
        $expiration = time() - $maxlifetime;
        $deleted    = 0;
        foreach (glob($pattern) as $file) {
            if (filemtime($file) < $expiration) {
                unlink($file);
                $deleted++;
            }
        }
        return $deleted;
    }

    /**
     * @inheritDoc
     */
    /** @noinspection PhpMethodNamingConventionInspection */
    public function create_sid()
    {
        $file = $this->savePath . DIRECTORY_SEPARATOR . $this->prefix;
        do {
            $id = session_create_id();
        } while (file_exists($file . $id));

        return ($this->lastCreatedId = $id);
    }

    /**
     * @inheritDoc
     */
    public function validateId($sessionId)
    {
        // This is a workaround for the problem that session ID is validated
        // even when create_sid() generates a collision free ID.
        // For more details see https://bugs.php.net/bug.php?id=77178.
        if ($sessionId === $this->lastCreatedId) {
            return true;
        }

        $file = $this->savePath . DIRECTORY_SEPARATOR . $this->prefix;
        return file_exists($file . $sessionId);
    }

    /**
     * @inheritDoc
     */
    public function updateTimestamp($sessionId, $sessionData)
    {
        $file = $this->savePath . DIRECTORY_SEPARATOR . $this->prefix . $sessionId;
        if ($handle = fopen($file, 'w')) {
            $result = touch($file) && flock($handle, LOCK_UN);
            fclose($handle);
            return $result;
        }
        return false;
    }
}