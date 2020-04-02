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
use Nano\Config\ConfigurationInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Basic HTTP Authentication implemented by a PSR-15 middleware.
 *
 * Basic HTTP Authentication middleware can be configured in the "auth.php"
 * configuration file.
 *
 * Available options for this class are:
 *
 * - `realm`: the string assigned by the server to identify the protection
 *   space, default: "Restricted".
 *
 * - `secure`: perform basic HTTP authentication only through a secure
 *   connection (HTTPS); default: `true`.
 *
 * - `whitelist`: the list of host that can be authenticated through an
 *   insecure channel; default: ['localhost', '127.0.0.1'].
 *
 * @see https://tools.ietf.org/html/rfc7617
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class BasicHttpAuthMiddleware extends AuthMiddleware
{
    /**
     * @var string
     */
    private $realm;

    /**
     * @inheritDoc
     */
    public function __construct(ContainerInterface $container,
                                ConfigurationInterface $config,
                                LoggerInterface $logger = null)
    {
        parent::__construct($container, $config, $logger);

        $this->realm = $this->getConfig('http_basic.realm', 'Restricted');
    }

    /**
     * Get the string assigned by the server to identify the protection space.
     *
     * @return string Returns the realm value.
     */
    public function getRealm(): string
    {
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
            $whitelist = $this->getConfig('http_basic.whitelist', ['localhost', '127.0.0.1']);

            if ($this->getConfig('http_basic.secure', true) &&
                !in_array($host, $whitelist)
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
            ->withHeader('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
    }
}