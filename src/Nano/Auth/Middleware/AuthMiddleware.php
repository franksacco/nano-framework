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

namespace Nano\Auth\Middleware;

use Nano\Auth\AuthenticableInterface;
use Nano\Auth\BasicGuard;
use Nano\Auth\Exception\NotAuthenticatedException;
use Nano\Auth\Exception\UnexpectedValueException;
use Nano\Auth\GuardInterface;
use Nano\Config\ConfigurationInterface;
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
 * Abstract implementation of an authentication middleware.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AuthMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The attribute name used to attach user to the server request.
     */
    const USER_ATTRIBUTE = 'authenticated-user';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigurationInterface
     */
    protected $config;

    /**
     * @var GuardInterface
     */
    protected $guard;

    /**
     * Initialize the authentication middleware.
     *
     * @param ContainerInterface $container The DI container.
     * @param ConfigurationInterface $config The application configuration.
     * @param LoggerInterface $logger [optional] The PSR-3 logger instance.
     *
     * @throws UnexpectedValueException if the guard class not implements
     *   {@see GuardInterface} interface.
     */
    public function __construct(ContainerInterface $container,
                                ConfigurationInterface $config,
                                ?LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->config    = $config;
        $this->logger    = $logger ?: new NullLogger();

        $this->setGuard();
    }

    /**
     * Set the authentication guard used by this class.
     *
     * If this method is called without the parameter, the guard class
     * set in the `auth.guard` configuration key is used.
     *
     * @param GuardInterface $guard [optional] The authentication guard.
     *
     * @throws UnexpectedValueException if the guard class not implements
     *   {@see GuardInterface} interface.
     */
    public function setGuard(GuardInterface $guard = null)
    {
        if ($guard === null) {
            $guard = $this->config->get('auth.guard', BasicGuard::class);
            if (is_string($guard) && $this->container->has($guard)) {
                $guard = $this->container->get($guard);
            }
            if (! $guard instanceof GuardInterface) {
                throw new UnexpectedValueException(sprintf(
                    'Authentication guard must implements %s',
                    GuardInterface::class
                ));
            }
        }
        $this->guard = $guard;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $user = $this->authenticate($request);

            $this->logger->notice(
                '[AUTH] User "{id}" authenticated',
                ['id' => $user->getIdentifier()]
            );
            return $handler->handle(
                $request->withAttribute(self::USER_ATTRIBUTE, $user)
            );

        } catch (NotAuthenticatedException $exception) {

            $this->logger->notice(
                '[AUTH] User not authenticated: {message}',
                ['message' => $exception->getMessage()]
            );
            return $this->processError($request, $handler);
        }
    }

    /**
     * Tries to authenticate a user using information in the server request.
     *
     * @param ServerRequestInterface $request The server request.
     * @return AuthenticableInterface Returns the user object.
     *
     * @throws NotAuthenticatedException if authentication is not successful.
     */
    abstract protected function authenticate(ServerRequestInterface $request): AuthenticableInterface;

    /**
     * Handle a failure during authentication.
     *
     * @param ServerRequestInterface $request The server request.
     * @param RequestHandlerInterface $handler The request handler.
     * @return ResponseInterface Returns the server response.
     */
    protected function processError(ServerRequestInterface $request,
                                    RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}
