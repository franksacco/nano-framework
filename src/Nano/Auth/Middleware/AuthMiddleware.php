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
use Nano\Config\ConfigurationAwareTrait;
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
 * This middleware can be configured through the configuration instance
 * provided to the constructor. Available options for this class are:
 *
 * - `guard`: the class that defines rules for user authentication
 *   implementing {@see GuardInterface}; default: {@see BasicGuard}.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AuthMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use ConfigurationAwareTrait;
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
     * @var GuardInterface|null
     */
    protected $guard;

    /**
     * Initialize the authentication middleware.
     *
     * @param ContainerInterface $container The DI container.
     * @param ConfigurationInterface $config The authentication configuration.
     * @param LoggerInterface|null $logger [optional] The PSR-3 logger instance.
     */
    public function __construct(ContainerInterface $container,
                                ConfigurationInterface $config,
                                LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->setConfiguration($config);
        $this->setLogger($logger ?: new NullLogger());
    }

    /**
     * Resolve the default authentication guard according to configuration.
     *
     * @return GuardInterface Returns the default authentication guard.
     *
     * @throws UnexpectedValueException if the guard class not implements
     *   {@see GuardInterface} interface.
     */
    protected function getDefaultGuard(): GuardInterface
    {
        $guard = $this->config->get('guard', BasicGuard::class);
        if (is_string($guard) && $this->container->has($guard)) {
            $guard = $this->container->get($guard);
        }

        if (! $guard instanceof GuardInterface) {
            throw new UnexpectedValueException(sprintf(
                'Authentication guard set in configuration not implements %s',
                GuardInterface::class
            ));
        }
        return $guard;
    }

    /**
     * Get the authentication guard used by this middleware.
     *
     * If not set, the guard will be resolved according to configuration.
     *
     * @return GuardInterface Returns the authentication guard.
     *
     * @throws UnexpectedValueException if the guard class not implements
     *   {@see GuardInterface} interface.
     */
    public function getGuard(): GuardInterface
    {
        if ($this->guard === null) {
            $this->guard = $this->getDefaultGuard();
        }
        return $this->guard;
    }

    /**
     * Set the authentication guard used by this middleware.
     *
     * @param GuardInterface $guard The authentication guard.
     */
    public function setGuard(GuardInterface $guard): void
    {
        $this->guard = $guard;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $user = $this->authenticate($request);

            $this->logger->info(
                'User {id} authenticated using identifier "{auth}"', [
                    'id'   => $user->getId(),
                    'auth' => $user->getAuthIdentifier()
            ]);
            return $handler->handle(
                $request->withAttribute(self::USER_ATTRIBUTE, $user)
            );

        } catch (NotAuthenticatedException $exception) {

            $this->logger->notice(
                'User not authenticated: {message}',
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
        // By default, continue the execution of the middleware queue.
        return $handler->handle($request);
    }
}