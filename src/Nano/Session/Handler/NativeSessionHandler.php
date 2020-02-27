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

use SessionHandler;
use SessionUpdateTimestampHandlerInterface;

/**
 * Session handler based on native session file handler.
 *
 * The behavior of {@see open()}, {@see read()}, {@see write()},
 * {@see close()}, {@see destroy()} and {@see gc()} is inherited
 * from {@see SessionHandler} class.
 * In addition to these methods, this class implements the
 * {@see SessionUpdateTimestampHandlerInterface} interface in order to give
 * the possibility to put in place <b>strict mode</b> and
 * <b>lazy_write mode</b>.
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class NativeSessionHandler extends SessionHandler implements SessionUpdateTimestampHandlerInterface
{
    /**
     * @var string
     */
    private $savePath = '';

    /**
     * @var string
     */
    private $lastCreatedId;

    /**
     * @inheritDoc
     */
    public function open($savePath, $name): bool
    {
        $path = explode(';', $savePath);
        $path = rtrim(end($path), DIRECTORY_SEPARATOR);
        $this->savePath = $path === '' ? sys_get_temp_dir() : $path;

        // Be sure that file storage is used.
        ini_set('session.save_handler', 'files');

        return parent::open($savePath, $name);
    }

    /**
     * @inheritDoc
     */
    /** @noinspection PhpMethodNamingConventionInspection */
    public function create_sid(): string
    {
        return ($this->lastCreatedId = parent::create_sid());
    }

    /**
     * @inheritDoc
     */
    public function validateId($id): bool
    {
        // This is a workaround for the problem that session ID is validated
        // even when create_sid() generates a collision free ID.
        // For more details see https://bugs.php.net/bug.php?id=77178.
        if ($id === $this->lastCreatedId) {
            return true;
        }

        $file = $this->savePath . DIRECTORY_SEPARATOR . 'sess_' . $id;
        return file_exists($file);
    }

    /**
     * @inheritDoc
     */
    public function updateTimestamp($id, $data): bool
    {
        $file = $this->savePath . DIRECTORY_SEPARATOR . 'sess_' . $id;
        return touch($file);
    }
}