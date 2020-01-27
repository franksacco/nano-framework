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

use DateInterval;
use DateTime;
use Laminas\Diactoros\Response\RedirectResponse;
use Nano\Auth\AuthenticableInterface;
use Nano\Auth\Exception\NotAuthenticatedException;
use Nano\Auth\Exception\UnexpectedValueException;
use Nano\Session\SessionInterface;
use Nano\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Session Authentication implemented by a PSR-15 middleware.
 *
 * This middleware requires a {@see SessionInterface} instance in the request
 * session attribute to work.
 *
 * Session Authentication middleware can be configured through the
 * `session_auth` key in application settings.
 * Available options for this class are:
 *  - `expiration`: the time-to-live of session's validity in seconds;
 *   default: 1200.
 *  - `redirect`: whether to enable redirection when authentication fails;
 *   default: TRUE.
 *  - `redirect_path`: the path of the redirection on authentication failure;
 *   default: '/login'.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class SessionAuthMiddleware extends AuthMiddleware
{
    protected const DATETIME_FORMAT = 'Y-m-d H:i:s';

    protected const AUTH_SESSION_USER    = 'auth-session-user';
    protected const AUTH_SESSION_UPDATED = 'auth-session-updated';

    /**
     * @inheritDoc
     *
     * @throws UnexpectedValueException if a valid session is not provided.
     */
    protected function authenticate(ServerRequestInterface $request): AuthenticableInterface
    {
        $session = $this->getSession($request);
        try {
            $user    = $this->parseUser($session);
            $updated = $this->parseUpdated($session);

            if ($this->isExpired($updated)) {
                throw new NotAuthenticatedException('Session expired');
            }
            $session->set(self::AUTH_SESSION_UPDATED,
                (new DateTime())->format(self::DATETIME_FORMAT)
            );
            return $user;

        } catch (NotAuthenticatedException $exception) {
            self::logout($session);
            throw $exception;
        }
    }

    /**
     * Retrieve the session instance from server request.
     *
     * @param ServerRequestInterface $request The server request.
     * @return SessionInterface Returns the session instance.
     *
     * @throws UnexpectedValueException if a valid session is not provided.
     */
    protected function getSession(ServerRequestInterface $request): SessionInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if (! $session instanceof SessionInterface) {
            throw new UnexpectedValueException(sprintf(
                'Auth middleware error: %s instance required',
                SessionInterface::class
            ));
        }
        return $session;
    }

    /**
     * Parse authenticated user from session data.
     *
     * @param SessionInterface $session The session instance.
     * @return AuthenticableInterface Returns the user instance.
     *
     * @throws NotAuthenticatedException if data is not valid.
     */
    protected function parseUser(SessionInterface $session): AuthenticableInterface
    {
        $userId = $session->get(self::AUTH_SESSION_USER);
        if (! is_string($userId)) {
            throw new NotAuthenticatedException('User id not exists in session');
        }
        $user = $this->guard->getProvider()->getUserById($userId);
        if ($user === null) {
            throw new NotAuthenticatedException('Invalid user id stored in session');
        }
        return $user;
    }

    /**
     * Parse updated datetime from session data.
     *
     * @param SessionInterface $session The session instance.
     * @return DateTime Returns the updated datetime.
     *
     * @throws NotAuthenticatedException if data is not valid.
     */
    protected function parseUpdated(SessionInterface $session): DateTime
    {
        $updated = DateTime::createFromFormat(
            '!' . self::DATETIME_FORMAT,
            $session->get(self::AUTH_SESSION_UPDATED, '')
        );
        if ($updated === false) {
            throw new NotAuthenticatedException('Invalid datetime stored in session');
        }
        return $updated;
    }

    /**
     * Check if the session is expired.
     *
     * @param DateTime $updated The datetime of the last update.
     * @return bool Returns TRUE if the session is expired, FALSE otherwise.
     */
    protected function isExpired(DateTime $updated): bool
    {
        $expiration = (int) $this->config->get('session_auth.expiration', 1200);
        try {
            $updated = (clone $updated)->add(new DateInterval("T{$expiration}S"));
            return $updated < new DateTime();
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    protected function processError(ServerRequestInterface $request,
                                    RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->config->get('session_auth.redirect', true)) {
            $url = $this->config->get('base_url') .
                $this->config->get('session_auth.redirect_path', '/login');
            return new RedirectResponse($url);
        }
        return $handler->handle($request);
    }

    /**
     * Initialize the authentication data in the session.
     *
     * @param SessionInterface $session The session instance.
     * @param AuthenticableInterface $user The the authenticate user.
     */
    public static function login(SessionInterface $session, AuthenticableInterface $user)
    {
        $session->set(self::AUTH_SESSION_USER, $user->getId());
        $session->set(self::AUTH_SESSION_UPDATED, date(self::DATETIME_FORMAT));
        $session->regenerate(true);
    }

    /**
     * Clear authentication data from session.
     *
     * @param SessionInterface $session The session instance.
     */
    public static function logout(SessionInterface $session)
    {
        $session->remove(self::AUTH_SESSION_USER);
        $session->remove(self::AUTH_SESSION_UPDATED);
        $session->regenerate(true);
    }
}
