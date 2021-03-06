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

use Laminas\Diactoros\Response\EmptyResponse;
use Nano\Auth\Exception\NotAuthenticatedException;
use Nano\Auth\AuthenticableInterface;
use Nano\Auth\Exception\UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Basic HTTP Authentication implemented by a PSR-15 middleware.
 *
 * This middleware can be configured through the configuration instance
 * provided to the constructor. Available options for this class are:
 *
 * - `guard`: the class that defines rules for user authentication
 *   implementing {@see GuardInterface}; default: {@see BasicGuard}.
 *
 * - `http_basic.realm`: the string assigned by the server to identify the
 *   protection space, default: "Restricted".
 *
 * - `http_basic.secure`: perform basic HTTP authentication only through a
 *   secure connection (HTTPS); default: `true`.
 *
 * - `http_basic.allowlist`: the list of host that can be authenticated through
 *   an insecure channel; default: `['localhost', '127.0.0.1']`.
 *
 * @see https://tools.ietf.org/html/rfc7617
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class BasicHttpAuthMiddleware extends AuthMiddleware
{
    /**
     * @var string|null
     */
    private $realm;

    /**
     * Get the string assigned by the server to identify the protection space.
     *
     * If not set, the realm will be resolved according to configuration.
     *
     * @return string Returns the realm value.
     */
    public function getRealm(): string
    {
        if ($this->realm === null) {
            $this->realm = $this->getConfig('http_basic.realm', 'Restricted');
        }
        return $this->realm;
    }

    /**
     * Set the string assigned by the server to identify the protection space.
     *
     * @param string $realm The realm value.
     */
    public function setRealm(string $realm): void
    {
        $this->realm = $realm;
    }

    /**
     * @inheritDoc
     */
    protected function authenticate(ServerRequestInterface $request): AuthenticableInterface
    {
        $scheme = $request->getUri()->getScheme();
        if ($scheme !== 'https') {
            $host      = $request->getUri()->getHost();
            $allowlist = $this->getConfig('http_basic.allowlist', ['localhost', '127.0.0.1']);

            if ($this->getConfig('http_basic.secure', true) &&
                !in_array($host, $allowlist)
            ) {
                throw new UnexpectedValueException('Cannot perform basic HTTP ' .
                    'authentication through an insecure channel');
            }
        }

        $header = trim($request->getHeaderLine('Authorization'));
        if (!preg_match('/^Basic\s+(.*)$/i', $header, $matches)) {
            throw new NotAuthenticatedException('Invalid or empty "Authorization" header');
        }

        $credentials = explode(":", base64_decode($matches[1]), 2);
        if (count($credentials) !== 2) {
            throw new NotAuthenticatedException('Invalid credentials format in "Authorization" header');
        }
        list($username, $password) = $credentials;

        return $this->getGuard()->authenticateByCredentials($username, $password);
    }

    /**
     * @inheritDoc
     */
    protected function processError(ServerRequestInterface $request,
                                    RequestHandlerInterface $handler): ResponseInterface
    {
        return (new EmptyResponse())
            ->withStatus(401)
            ->withHeader('WWW-Authenticate',
                sprintf('Basic realm="%s", charset="UTF-8"', $this->getRealm()));
    }
}