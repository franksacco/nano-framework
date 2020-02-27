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

namespace Nano\Session;

use Nano\Config\ConfigurationInterface;
use Nano\Session\Exception\SessionException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Wrapper class for the native PHP session functions.
 *
 * Session management can be configured through the "session" key in
 * application settings.
 * List of available options:
 *  - "ini": list of session ini settings. The keys should not include the
 *   'session.' prefix.<br>In according to
 *   {@link https://secure.php.net/manual/en/session.security.ini.php official recommendation}
 *   about session security, default ini settings are:<br>
 *    'cookie_domain'             => '',<br>
 *    'cookie_httponly'           => 1,<br>
 *    'cookie_lifetime'           => 0,<br>
 *    'cookie_path'               => '/',<br>
 *    'cookie_samesite'           => 1 (PHP 7 >= 7.3),<br>
 *    'cookie_secure'             => 1,<br>
 *    'gc_divisor'                => 100,<br>
 *    'gc_maxlifetime'            => 1200,<br>
 *    'gc_probability'            => 1,<br>
 *    'lazy_write'                => 1,<br>
 *    'name'                      => 'SID',<br>
 *    'save_path'                 => '',<br>
 *    'serialize_handler'         => 'php_serialize',<br>
 *    'sid_bits_per_character'    => 6,<br>
 *    'sid_length'                => 48,<br>
 *    'use_cookies'               => 1,<br>
 *    'use_only_cookies'          => 1,<br>
 *    'use_strict_mode'           => 1,<br>
 *    'use_trans_sid'             => 0<br>
 *  - "handler": custom session handler.<br>Can be an object or a class name
 *   that implements SessionHandlerInterface interface.
 *  - "log": enable verbose logging for debug; default: `false`.
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class NativeSession extends AbstractSession
{
    /**
     * @inheritDoc
     */
    public function __construct(ContainerInterface $container,
                                ConfigurationInterface $config,
                                LoggerInterface $logger = null)
    {
        parent::__construct($container, $config, $logger);

        if ($this->handler !== null) {
            // session_write_close() is not registered as a shutdown function
            // because SessionInterface::close() is already registered by
            // parent constructor.
            session_set_save_handler($this->handler, false);
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * @inheritDoc
     */
    public function getId() : string
    {
        return session_id();
    }

    /**
     * @inheritDoc
     *
     * If the session is active, this method has no effects.
     *
     * Note that <b>same-site</b> option is available only for PHP >= 7.3.
     */
    public function setCookieParams(int $lifetime = 0,
                                    ?string $path = null,
                                    ?string $domain = null,
                                    bool $secure = false,
                                    bool $httpOnly = false,
                                    ?string $sameSite = null)
    {
        if ($this->active) {
            return;
        }

        session_set_cookie_params($lifetime, $path, $domain, $secure, $httpOnly);

        if ($sameSite !== null && version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $this->configure(['cookie_samesite' => $sameSite]);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams() : array
    {
        $params = session_get_cookie_params();

        return [
            'expires'  => $params['lifetime'] === 0 ? null : time() + $params['lifetime'],
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httpOnly' => $params['httponly'],
            'sameSite' => $params['samesite'] ?? null
        ];
    }

    /**
     * @inheritDoc
     */
    public function start()
    {
        $this->logger->debug('[SESSION] Starting session...');
        if ($this->active || session_status() === PHP_SESSION_ACTIVE) {
            throw SessionException::alreadyStarted();
        }
        if (ini_get('session.use_cookies') and headers_sent()) {
            throw SessionException::headersSent();
        }
        if (!session_start()) {
            throw SessionException::startError();
        }
        $this->active = true;
        $this->data   = $_SESSION;
        $this->logger->debug('[SESSION] Session "{id}" started', ['id' => $this->getId()]);
    }

    /**
     * @inheritDoc
     */
    public function regenerate(bool $deleteOld = false)
    {
        $this->logger->debug('[SESSION] Regenerating session...');
        if (! $this->active) {
            $this->start();
        }
        $_SESSION = $this->data;
        if (! session_regenerate_id($deleteOld)) {
            throw SessionException::regenerateError();
        }
        $this->logger->debug('[SESSION] Session "{id}" regenerated', ['id' => $this->getId()]);
    }

    /**
     * @inheritDoc
     */
    public function destroy(bool $deleteCookie = false)
    {
        if (! $this->active) {
            $this->start();
        }
        $id = $this->getId();

        if (! session_destroy()) {
            throw SessionException::destroyError();
        }
        $this->active = false;
        $this->data   = [];
        $_SESSION     = [];

        if ($deleteCookie) {
            $params = session_get_cookie_params();
            setcookie(
                $this->getName(),
                '',
                time() - 86400, // now - one day
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        $this->logger->debug('[SESSION] Session "{id}" destroyed', compact('id'));
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        if (! $this->active) {
            return;
        }
        $id = $this->getId();

        $_SESSION = $this->data;
        session_write_close();
        $this->active = false;
        $this->data   = [];

        $this->logger->debug('[SESSION] Session "{id}" closed', compact('id'));
    }

    /**
     * @inheritDoc
     */
    public function gc()
    {
        if (($n = session_gc()) === false) {
            throw SessionException::gcError();
        }

        $this->logger->debug('[SESSION] Garbage collection deletes {n} sessions', compact('n'));
    }
}