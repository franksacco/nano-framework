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
use Nano\Auth\AuthenticableInterface;
use Nano\Auth\Exception\NotAuthenticatedException;
use Nano\Config\ConfigurationInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Digest HTTP Authentication implemented by a PSR-15 middleware.
 *
 * NOTE: this type of authentication requires that each user's secret was
 * saved in plaintext. This value is used to calculate every time the correct
 * response-challenge in order to compare the response provided by the client.
 *
 * Digest HTTP Authentication middleware can be configured in the "auth.php"
 * configuration file.
 *
 * Available options for this class are:
 *
 * - `realm`: the string assigned by the server to identify the protection
 *   space, default: "Restricted".
 *
 * @see https://tools.ietf.org/html/rfc7616
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class DigestHttpAuthMiddleware extends AuthMiddleware
{
    /**
     * @var string
     */
    private $realm;

    /**
     * @var string
     */
    private $nonce;

    /**
     * @inheritDoc
     */
    public function __construct(ContainerInterface $container,
                                ConfigurationInterface $config,
                                LoggerInterface $logger = null)
    {
        parent::__construct($container, $config, $logger);

        $this->realm = $this->getConfig('http_digest.realm', 'Restricted');
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
    public function setRealm(string $realm)
    {
        $this->realm = $realm;
    }

    /**
     * Get the nonce value
     *
     * @return string Returns the nonce value.
     */
    public function getNonce(): string
    {
        return $this->realm;
    }

    /**
     * Set the nonce value.
     *
     * If not set, the {@see uniqid()} function is used instead.
     *
     * @param string $nonce The nonce value.
     */
    public function setNonce(string $nonce)
    {
        $this->nonce = $nonce;
    }

    /**
     * @inheritDoc
     */
    protected function authenticate(ServerRequestInterface $request): AuthenticableInterface
    {
        $params = $this->parseHeader($request);
        $user   = $this->getGuard()->authenticateByAuthIdentifier($params['username']);

        $correctResponse = $this->calculateResponse($params, $user->getAuthSecret(), $request->getMethod());
        if ($params['response'] !== $correctResponse) {
            throw new NotAuthenticatedException('The value of response-challenge is not correct');
        }

        return $user;
    }

    /**
     * Parses Authorization header and returns parameters.
     *
     * @param ServerRequestInterface $request The server request.
     * @return array Returns the associative array of parameters with the
     *   following keys: "response", "username", "realm", "uri", "qop",
     *   "nonce", "cnonce" and "nc".
     *
     * @throws NotAuthenticatedException if header is empty or invalid.
     */
    private function parseHeader(ServerRequestInterface $request): array
    {
        $header = trim($request->getHeaderLine('Authorization'));
        if ($header === '') {
            throw new NotAuthenticatedException('Empty "Authorization" header');
        }

        $required = ['response', 'username', 'realm', 'uri', 'qop', 'nonce', 'cnonce', 'nc'];
        $pattern  = '@(' . implode('|', $required) . ')\s?=\s?"?([^",]+)"?@';
        if (preg_match_all($pattern, $header, $matches) !== count($required)) {
            throw new NotAuthenticatedException('Invalid format of "Authorization" header');
        }

        return array_combine($matches[1], $matches[2]);
    }

    /**
     * Calculates the correct response-challenge.
     *
     * @param array $params The associative array of parameters.
     * @param string $password The user password.
     * @param string $method The server request method.
     * @return string Returns the correct response.
     */
    private function calculateResponse(array $params, string $password, string $method): string
    {
        $a1 = $params['username'] . ':' . $params['realm'] . ':' . $password;
        $a2 = $method . ':' . $params['uri'];

        return md5(implode(':', [
            md5($a1),
            $params['nonce'],
            $params['nc'],
            $params['cnonce'],
            $params['qop'],
            md5($a2)
        ]));
    }

    /**
     * @inheritDoc
     */
    protected function processError(ServerRequestInterface $request,
                                    RequestHandlerInterface $handler): ResponseInterface
    {
        return (new EmptyResponse())
            ->withStatus(401)
            ->withHeader('WWW-Authenticate', sprintf(
                'Digest realm="%s",qop="auth",nonce="%s",opaque="%s"',
                $this->realm,
                $this->nonce ?: uniqid(),
                md5($this->realm)
            ));
    }
}