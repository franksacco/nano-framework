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
use Nano\Http\NanoTransformer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Middleware to handle session engine.
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class SessionMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const SESSION_ATTRIBUTE = 'session';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * SessionMiddleware constructor.
     *
     * @param ContainerInterface $container The DI container.
     * @param ConfigurationInterface $config The application configuration.
     * @param LoggerInterface $logger [optional] The PSR-3 logger instance.
     */
    public function __construct(ContainerInterface $container,
                                ConfigurationInterface $config,
                                LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->config    = $config;
        $this->logger    = $logger ?: new NullLogger();
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $useNative = $this->config->get('session.native', false);
        $session   = $useNative ?
            new NativeSession($this->container, $this->config, $this->logger) :
            new NanoSession($request, $this->container, $this->config, $this->logger);

        $session->start();

        $request  = $request->withAttribute(self::SESSION_ATTRIBUTE, $session);
        $response = $handler->handle($request);

        return $useNative ? $response :
            $this->injectSessionCookie($session, $response);
    }

    /**
     * Manually inject session cookie in the server response.
     *
     * @param SessionInterface $session The session instance.
     * @param ResponseInterface $response The server response.
     * @return ResponseInterface
     */
    private function injectSessionCookie(SessionInterface $session,
                                         ResponseInterface $response): ResponseInterface
    {
        $params = $session->getCookieParams();

        $response = NanoTransformer::toNanoResponse($response);
        return $response->withCookie(
            $session->getName(),
            $session->getId(),
            $params['expires'],
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httpOnly'],
            $params['sameSite']
        );
    }
}
