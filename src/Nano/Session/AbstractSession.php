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
use Nano\Session\Exception\ConfigurationException;
use Nano\Session\Exception\InvalidHandlerException;
use Nano\Utility\DotArrayAccessTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionHandlerInterface;

/**
 * Implements common behaviors for session objects.
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
 *   that implements SessionHandlerInterface interface.
 *  - `log`: enable verbose logging for debug; default: `false`.
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AbstractSession implements SessionInterface, LoggerAwareInterface
{
    use DotArrayAccessTrait, LoggerAwareTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigurationInterface
     */
    protected $config;

    /**
     * @var SessionHandlerInterface|null
     */
    protected $handler;

    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Initialize session.
     *
     * @param ContainerInterface $container The DI container.
     * @param ConfigurationInterface $config The application configuration.
     * @param LoggerInterface $logger [optional] The PSR-3 logger instance.
     *
     * @throws ConfigurationException if a session ini option fails to be set.
     * @throws InvalidHandlerException for an invalid Session Handler.
     */
    public function __construct(ContainerInterface $container,
                                ConfigurationInterface $config,
                                ?LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->config    = $config->fork('session');

        $this->handler = $this->parseHandler();
        $this->logger  = $this->config->get('log', false) && $logger ?
            $logger : new NullLogger();

        $iniOptions = (array) $this->config->get('ini', []);
        $iniOptions += $this->getDefaultIniOptions();
        $this->configure($iniOptions);

        // make sure that session is closed by this class on shutdown
        register_shutdown_function([$this, 'close']);
    }

    /**
     * Get the default session ini configuration.
     *
     * @return array
     */
    private function getDefaultIniOptions(): array
    {
        $iniOptions = [
            'cookie_domain'             => '',
            'cookie_httponly'           => 1,
            'cookie_lifetime'           => 0,
            'cookie_path'               => '/',
            'cookie_secure'             => 1,
            'gc_divisor'                => 100,
            'gc_maxlifetime'            => 1200,
            'gc_probability'            => 1,
            'lazy_write'                => 1,
            'name'                      => 'SID',
            'save_path'                 => '',
            'serialize_handler'         => 'php_serialize',
            'sid_bits_per_character'    => 6,
            'sid_length'                => 48,
            'use_cookies'               => 1,
            'use_only_cookies'          => 1,
            'use_strict_mode'           => 1,
            'use_trans_sid'             => 0,
        ];

        if (PHP_VERSION_ID >= 70300) {
            $iniOptions['cookie_samesite'] = 1;
        }

        return $iniOptions;
    }

    /**
     * Parse user-defined session handler.
     *
     * @return SessionHandlerInterface|null Returns a session handler instance
     *     if set, `null` otherwise.
     *
     * @throws InvalidHandlerException for an invalid Session Handler.
     */
    protected function parseHandler(): ?SessionHandlerInterface
    {
        $handler = $this->config->get('handler');
        if ($handler === null) {
            return null;
        }

        if (is_string($handler)) {
            if (! $this->container->has($handler)) {
                throw InvalidHandlerException::classNotExists();
            }
            $handler = $this->container->get($handler);
        }

        if (! $handler instanceof SessionHandlerInterface) {
            throw InvalidHandlerException::interfaceNotImplemented();
        }

        return $handler;
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @inheritDoc
     */
    public function configure(array $iniOptions)
    {
        foreach ($iniOptions as $key => $value) {
            if (ini_set('session.' . $key, (string) $value) === false) {
                throw new ConfigurationException(sprintf(
                    'Unable to configure the session: option %s with value %s failed',
                    $key, (string) $value
                ));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        if (! $this->active) {
            $this->start();
        }

        return $this->hasItem($this->data, $key);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        if (! $this->active) {
            $this->start();
        }

        return $this->getItem($this->data, $key, $default);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value)
    {
        if (! $this->active) {
            $this->start();
        }

        $this->setItem($this->data, $key, $value);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key)
    {
        if (! $this->active) {
            $this->start();
        }

        unset($this->data[$key]);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        if (! $this->active) {
            $this->start();
        }

        $this->data = [];
    }
}