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
use Nano\Auth\Exception\UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Digest HTTP Authentication implemented by a PSR-15 middleware.
 *
 * NOTE: this type of authentication requires that each user's secret was
 * saved in plaintext. This value is used to calculate every time the correct
 * response-challenge in order to compare the response provided by the client.
 *
 * This middleware can be configured through the configuration instance
 * provided to the constructor. Available options for this class are:
 *
 * - `guard`: the class that defines rules for user authentication
 *   implementing {@see GuardInterface}; default: {@see BasicGuard}.
 *
 * - `http_digest.realm`: the string assigned by the server to identify the
 *   protection space, default: "Restricted".
 *
 * @see https://tools.ietf.org/html/rfc7616
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class DigestHttpAuthMiddleware extends AuthMiddleware
{
    /**
     * The MD5 hashing algorithm.
     *
     * To maintain backward compatibility with
     * <a href="https://tools.ietf.org/html/rfc2617">RFC2617</a>, the MD5
     * algorithm is still supported but NOT RECOMMENDED.
     */
    public const ALGORITHM_MD5 = 'md5';
    /**
     * The SHA-256 hashing algorithm.
     */
    public const ALGORITHM_SHA256 = 'sha256';
    /**
     * The SHA-512/256 hashing algorithm.
     */
    public const ALGORITHM_SHA512256 = 'sha512/256';

    private const AVAILABLE_ALGORITHMS = [
        self::ALGORITHM_MD5,
        self::ALGORITHM_SHA256,
        self::ALGORITHM_SHA512256
    ];

    /**
     * @var string|null
     */
    private $realm;

    /**
     * @var string|null
     */
    private $nonce;

    /**
     * @var string
     */
    private $algorithm = self::ALGORITHM_SHA256;

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
            $this->realm = $this->getConfig('http_digest.realm', 'Restricted');
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
     * Get the nonce value.
     *
     * If not set, the {@see uniqid()} function is used instead.
     *
     * @return string Returns the nonce value.
     */
    public function getNonce(): string
    {
        if ($this->nonce === null) {
            $this->nonce = uniqid();
        }
        return $this->nonce;
    }

    /**
     * Set the nonce value.
     *
     * @param string $nonce The nonce value.
     */
    public function setNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }

    /**
     * Get the algorithm used for the challenge for hashing.
     *
     * If not set, the default hashing algorithm is
     * {@see DigestHttpAuthMiddleware::ALGORITHM_SHA256}.
     *
     * @return string Returns the algorithm using the
     *     <code>DigestHttpAuthMiddleware::ALGORITHM_*</code> constants.
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Set the algorithm used for the challenge.
     *
     * @param string $algorithm The algorithm using the
     *     <code>DigestHttpAuthMiddleware::ALGORITHM_*</code> constants.
     *
     * @throws UnexpectedValueException if the algorithm is not valid.
     */
    public function setAlgorithm(string $algorithm): void
    {
        if (! in_array($algorithm, self::AVAILABLE_ALGORITHMS)) {
            throw new UnexpectedValueException('The algorithm is not valid');
        }
        $this->algorithm = $algorithm;
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

        return $this->hash(implode(':', [
            $this->hash($a1),
            $params['nonce'],
            $params['nc'],
            $params['cnonce'],
            $params['qop'],
            $this->hash($a2)
        ]));
    }

    /**
     * Generate a hash value in according to the selected algorithm.
     *
     * @param string $data The data to be hashed.
     * @return string Returns a string containing the calculated
     *     message digest as lowercase hexadecimal digits.
     */
    private function hash(string $data)
    {
        return hash($this->getAlgorithm(), $data);
    }

    /**
     * @inheritDoc
     */
    protected function processError(ServerRequestInterface $request,
                                    RequestHandlerInterface $handler): ResponseInterface
    {
        $algorithms = [
            self::ALGORITHM_MD5       => 'MD5',
            self::ALGORITHM_SHA256    => 'SHA-256',
            self::ALGORITHM_SHA512256 => 'SHA-512-256'
        ];

        return (new EmptyResponse())
            ->withStatus(401)
            ->withHeader('WWW-Authenticate', sprintf(
                'Digest realm="%s",qop="auth",algorithm=%s,nonce="%s",opaque="%s"',
                $this->getRealm(),
                $algorithms[$this->getAlgorithm()],
                $this->getNonce(),
                md5($this->getRealm())
            ));
    }
}