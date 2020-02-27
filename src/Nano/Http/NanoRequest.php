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

use Laminas\Diactoros\Exception\InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Extender of PSR-7 HTTP request for Nano framework.
 *
 * This class is based on {@see ServerRequest} from Laminas Diactoros package.
 *
 * @package Nano\Http
 * @author Francesco Saccani <saccani.francesco@gmail.com>
 */
class NanoRequest extends ServerRequest
{
    /**
     * Create a NanoRequest.
     *
     * @param array $serverParams Server parameters, typically from $_SERVER.
     * @param array $uploadedFiles Upload file information, a tree of UploadedFiles.
     * @param null|string|UriInterface $uri URI for the request, if any.
     * @param null|string $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface $body Message body, if any.
     * @param array $headers Headers for the message, if any.
     * @param array $cookies Cookies for the message, if any.
     * @param array $queryParams Query params for the message, if any.
     * @param null|array|object $parsedBody The deserialized body parameters, if any.
     * @param string $protocol HTTP protocol version.
     *
     * @throws InvalidArgumentException for any invalid value.
     */
    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        $uri = null,
        $method = null,
        $body = 'php://input',
        array $headers = [],
        array $cookies = [],
        array $queryParams = [],
        $parsedBody = null,
        $protocol = '1.1'
    ) {
        parent::__construct(
            $serverParams,
            $uploadedFiles,
            $uri,
            $method,
            $body,
            $headers,
            $cookies,
            $queryParams,
            $parsedBody,
            $protocol
        );
    }
}