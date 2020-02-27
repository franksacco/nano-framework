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
use Nano\Session\Exception\InvalidHandlerException;
use Nano\Session\Exception\SessionException;
use Nano\Session\Handler\FileSessionHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use SessionHandler;
use SessionHandlerInterface;

/**
 * Implementation of session engine that not uses native session functions.
 *
 * Session management can be configured through the "session" key in
 * application settings.
 * List of available options:
 *  - `ini`: list of session ini settings. The keys should not include the
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
 *  - `handler`: custom session handler.<br>Can be an object or a class name
 *   that implements SessionHandlerInterface interface. If a session handler
 *   is not given, by default {@see FileSessionHandler} is used instead.<br>
 *   Note that the session handler must not extend {@see SessionHandler}
 *   class because this class need native session engine to work.
 *  - `log`: enable verbose logging for debug; default: FALSE.
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class NanoSession extends AbstractSession
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $id = '';

    /**
     * @var array
     */
    private $cookieParams;

    /**
     * @inheritDoc
     * @param ServerRequestInterface $request The server request.
     */
    public function __construct(ServerRequestInterface $request,
                                ContainerInterface $container,
                                ConfigurationInterface $config,
                                LoggerInterface $logger = null)
    {
        parent::__construct($container, $config, $logger);

        $this->name    = ini_get('session.name');
        $this->request = $request;
        $this->initializeCookieParams();
    }

    /**
     * @inheritDoc
     */
    protected function parseHandler(): ?SessionHandlerInterface
    {
        $handler = parent::parseHandler();
        if ($handler === null) {
            return new FileSessionHandler($this->config);
        }
        if ($handler instanceof SessionHandler) {
            throw InvalidHandlerException::extendsSessionHandler();
        }
        return $handler;
    }

    /**
     * Initialize session cookie parameters.
     */
    private function initializeCookieParams()
    {
        $this->setCookieParams(
            (int) ini_get('session.cookie_lifetime'),
            ini_get('session.cookie_path'),
            ini_get('session.cookie_domain'),
            ini_get('session.cookie_secure') === '1',
            ini_get('session.cookie_httponly') === '1',
            PHP_VERSION_ID < 70300 ? 'Strict' : ini_get('session.cookie_samesite')
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function setCookieParams(int $lifetime,
                                    ?string $path = null,
                                    ?string $domain = null,
                                    bool $secure = false,
                                    bool $httpOnly = false,
                                    ?string $sameSite = null)
    {
        $this->cookieParams = [
            'expires'  => $lifetime === 0 ? 0 : time() + $lifetime,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite
        ];
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * Create a new session ID.
     *
     * @return string
     */
    private function createId(): string
    {
        if (method_exists($this->handler, 'create_sid')) {
            return $this->handler->create_sid();
        }
        return session_create_id();
    }

    /**
     * Checks whether the session is initialized.
     *
     * @param string $sessionId The session ID to validate.
     * @return bool Returns TRUE if the session ID is valid, FALSE otherwise.
     */
    private function validateId(string $sessionId): bool
    {
        if (ini_get('session.use_strict_mode')
            && method_exists($this->handler, 'validateId')
        ) {
            return $this->handler->validateId($sessionId);
        }
        return true;
    }

    /**
     * Does Garbage Collection based on probability.
     */
    private function probabilityGC()
    {
        $divisor     = (int) ini_get('session.gc_divisor');
        $probability = (int) ini_get('session.gc_probability');

        if (rand(1, $divisor) <= $probability) {
            $this->gc();
        }
    }

    /**
     * Searches session ID in server request.
     *
     * Note that currently this method searches session ID only in cookies
     * sent in server request.
     */
    private function findIdFromRequest()
    {
        $cookies  = $this->request->getCookieParams();
        $this->id = $cookies[$this->name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function start()
    {
        $this->logger->debug('[SESSION] Starting session...');
        if ($this->active) {
            throw SessionException::alreadyStarted();
        }
        if (headers_sent()) {
            throw SessionException::headersSent();
        }
        $this->probabilityGC();

        if (! $this->handler->open(ini_get('session.save_path'), $this->name)) {
            throw SessionException::startError();
        }
        $this->findIdFromRequest();
        if ($this->id === null || !$this->validateId($this->id)) {
            $this->id = $this->createId();
        }
        $this->active = true;

        $data = $this->handler->read($this->id);
        if ($data !== '') {
            $this->data = unserialize($data);
            if ($this->data === false) {
                $this->destroy();
                throw SessionException::notUnserializableData();
            }
        }
        $this->logger->debug('[SESSION] Session "{id}" started', ['id' => $this->id]);
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
        $result = $deleteOld ?
            $this->handler->destroy($this->id) :
            $this->handler->write($this->id, serialize($this->data));
        if (!$result || !$this->handler->close()) {
            throw SessionException::regenerateError();
        }

        if (! $this->handler->open(ini_get('session.save_path'), $this->name)) {
            throw SessionException::regenerateError();
        }
        $this->id = $this->createId();

        $this->handler->read($this->id);
        $this->logger->debug('[SESSION] Session "{id}" regenerated', ['id' => $this->id]);
    }

    /**
     * @inheritDoc
     */
    public function destroy(bool $deleteCookie = false)
    {
        if (! $this->active) {
            $this->start();
        }
        $result = $this->handler->destroy($this->id);
        if (! ($result && $this->handler->close())) {
            throw SessionException::destroyError();
        }

        $this->logger->debug('[SESSION] Session "{id}" destroyed', ['id' => $this->id]);

        $this->active = false;
        $this->data   = [];
        $this->id     = '';

        if ($deleteCookie) {
            $this->cookieParams['expires'] = time() - 86400;
        }
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        if (! $this->active) {
            return;
        }
        $result = $this->handler->write($this->id, serialize($this->data));
        if (! ($result && $this->handler->close())) {
            throw SessionException::closeError();
        }

        $this->logger->debug('[SESSION] Session "{id}" closed', ['id' => $this->id]);

        $this->active = false;
        $this->data   = [];
        $this->id     = '';
    }

    /**
     * @inheritDoc
     */
    public function gc()
    {
        $maxlifetime = (int)ini_get('session.gc_maxlifetime');
        if (($n = $this->handler->gc($maxlifetime)) === false) {
            throw SessionException::gcError();
        }

        $this->logger->debug('[SESSION] Garbage collection deletes {n} sessions', compact('n'));
    }
}