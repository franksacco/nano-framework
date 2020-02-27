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

namespace Nano\Controller\Helper;

use Nano\Auth\AuthenticableInterface;
use Nano\Auth\BasicGuard;
use Nano\Auth\Exception\NotAuthenticatedException;
use Nano\Auth\GuardInterface;
use Nano\Auth\Middleware\AuthMiddleware;
use Nano\Auth\Middleware\SessionAuthMiddleware;
use Nano\Config\ConfigurationInterface;
use Nano\Controller\AbstractController;
use Nano\Controller\Exception\UnexpectedValueException;
use Nano\Session\SessionInterface;
use Nano\Session\SessionMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Controller helper for session authentication logic.
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class AuthHelper extends AbstractHelper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var AuthenticableInterface
     */
    private $user;

    /**
     * @var GuardInterface
     */
    protected $guard;

    /**
     * @inheritDoc
     * @param ConfigurationInterface $config The application settings.
     * @param LoggerInterface $logger [optional] The PSR-3 logger instance.
     *
     * @throws UnexpectedValueException if a valid session is not provided.
     * @throws UnexpectedValueException if the guard class not implements
     *   {@see GuardInterface} interface.
     */
    public function __construct(AbstractController $controller,
                                ConfigurationInterface $config,
                                LoggerInterface $logger = null)
    {
        parent::__construct($controller);
        $request = $controller->getRequest();

        $this->config  = $config;
        $this->logger  = $logger ?: new NullLogger();
        $this->session = $this->getSession($request);

        $user = $request->getAttribute(AuthMiddleware::USER_ATTRIBUTE);
        if ($user instanceof AuthenticableInterface) {
            $this->user = $user;
        }

        $this->setGuard();
    }

    /**
     * Retrieves the session instance from server request.
     *
     * @param ServerRequestInterface $request The server request.
     * @return SessionInterface Returns the session instance.
     *
     * @throws UnexpectedValueException if a valid session is not provided.
     */
    private function getSession(ServerRequestInterface $request): SessionInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if (! $session instanceof SessionInterface) {
            throw new UnexpectedValueException(sprintf(
                'Auth helper error: %s instance required',
                SessionInterface::class
            ));
        }

        return $session;
    }

    /**
     * Set the authentication guard used by this class.
     *
     * If this method is called without the parameter, the guard class
     * set in the "auth.guard" configuration key is used.
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
            $container = $this->controller->getContainer();
            if (is_string($guard) && $container->has($guard)) {
                $guard = $container->get($guard);
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
     * Check if the current user is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    /**
     * Retrieve the authenticated user.
     *
     * @return AuthenticableInterface|null Returns the user instance if
     *   authenticated, `null` otherwise.
     */
    public function getUser(): ?AuthenticableInterface
    {
        return $this->user;
    }

    /**
     * Try to login the user through provided credentials.
     *
     * @param string $username The provided user name (e.g. email).
     * @param string $secret The provided user secret (e.g. password).
     * @return bool Returns `true` if login is successful, `false` otherwise.
     */
    public function login(string $username, string $secret): bool
    {
        try {
            $this->user = $this->guard->authenticateByCredentials($username, $secret);
            $this->logger->info(
                    '[AUTH] User with id "{id}" is logged in',
                ['id' => $this->user->getId()]
            );
        } catch (NotAuthenticatedException $exception) {
            $this->logger->info(
                '[AUTH] Login failed: {message}',
                ['message' => $exception->getMessage()]
            );
            return false;
        }
        SessionAuthMiddleware::login($this->session, $this->user);
        return true;
    }

    /**
     * Logout the authenticated user.
     *
     * @throws NotAuthenticatedException if the user is not authenticated.
     */
    public function logout()
    {
        if (! $this->isAuthenticated()) {
            throw new NotAuthenticatedException('User must be authenticated in order to logout');
        }

        $this->logger->info(
            '[AUTH] User with id "{id}" is logged out',
            ['id' => $this->user->getId()]
        );

        SessionAuthMiddleware::logout($this->session);
        $this->user = null;
    }
}