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

namespace Nano\Http;

use DateTime;
use DateTimeInterface;
use Psr\Http\Message\StreamInterface;
use Laminas\Diactoros\Exception\InvalidArgumentException;
use Laminas\Diactoros\Response;

/**
 * Extend a PSR-7 HTTP response for Nano framework.
 *
 * This class is based on {@see Response} from Laminas Diactoros package.
 *
 * @package Nano\Http
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class NanoResponse extends Response
{
    /**
     * Possible value for the 'SameSite' cookie attribute.
     *
     * In this mode, the cookie is withheld with any cross-site usage.
     */
    const COOKIE_SAMESITE_STRICT = 'Strict';

    /**
     * Possible value for the 'SameSite' cookie attribute.
     *
     * In this mode, only some cross-site usage is allowed: specifically
     * if the request is a GET request and the request is top-level.
     */
    const COOKIE_SAMESITE_LAX = 'Lax';

    /**
     * Create a NanoResponse.
     *
     * @param string|resource|StreamInterface $body Stream identifier and/or
     *  actual stream resource.
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     *
     * @throws InvalidArgumentException on any invalid element.
     */
    public function __construct($body = 'php://memory', $status = 200, array $headers = [])
    {
        parent::__construct($body, $status, $headers);
    }

    /**
     * Add a cookie header to the server response.
     *
     * @param string $name The name of the cookie. The name must contains only
     *   alphanumeric or underscore characters (a-z, A-Z, 0-9, _).
     * @param string $value The value of the cookie.
     * @param int|DateTimeInterface|null $expires [optional] The Unix timestamp
     *   or {@see DateTimeInterface} instance when the cookie expires; if not set
     *   or set to 0, the cookie will get removed when the client is closed.
     * @param string|null $path [optional] The path on the server in which the
     *   cookie will be available on.
     * @param string|null $domain [optional] The (sub)domain that the cookie
     *   is available to.
     * @param bool $secure [optional] When TRUE the cookie will only be
     *   transmitted over a secure HTTPS connection.
     * @param bool $httpOnly [optional] When TRUE the cookie will be made
     *   accessible only through the HTTP protocol.
     * @param string|null $sameSite [optional] When COOKIE_SAMESITE_STRICT
     *   prevents the browser from sending this cookie along with cross-site
     *   requests, when is COOKIE_SAMESITE_LAX only some cross-site usage is
     *   allowed.
     * @return NanoResponse
     *
     * @throws InvalidArgumentException if the cookie name or expire timestamp is invalid.
     */
    public function withCookie(string $name,
                               string $value,
                               $expires = null,
                               ?string $path = null,
                               ?string $domain = null,
                               bool $secure = false,
                               bool $httpOnly = false,
                               ?string $sameSite = null): NanoResponse
    {
        if (! preg_match('/^[\\w]+$/', $name)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid cookie name "%s", it must contains only alphanumeric or underscore characters',
                $name
            ));
        }
        $headerValue = $name . '=' . urlencode($value);

        if (is_int($expires) && $expires > 0) {
            try {
                $expires = (new DateTime())->setTimestamp($expires);
            } catch (\Exception $e) {}
        }
        if ($expires instanceof DateTimeInterface) {
            $headerValue .= '; Expires=' . $expires->format(DateTime::COOKIE);
        }

        if (!empty($path)) {
            $headerValue .= '; Path=' . $path;
        }

        if (!empty($domain)) {
            $headerValue .= '; Domain=' . $domain;
        }

        if ($secure) {
            $headerValue .= '; Secure';
        }

        if ($httpOnly) {
            $headerValue .= '; HttpOnly';
        }

        if (in_array($sameSite, [self::COOKIE_SAMESITE_STRICT, self::COOKIE_SAMESITE_LAX], true)) {
            $headerValue .= '; SameSite=' . $sameSite;
        }

        return $this->withAddedHeader('Set-Cookie', $headerValue);
    }
}