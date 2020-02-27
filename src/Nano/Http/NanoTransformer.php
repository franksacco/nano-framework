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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Exception\InvalidArgumentException;

/**
 * Static class for transforming standard PSR-7 server request or response
 * into NanoRequest or NanoResponse respectively.
 *
 * @package Nano\Http
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class NanoTransformer
{
    /**
     * Transform a standard PSR-7 request into a NanoRequest.
     *
     * @param ServerRequestInterface $request The PSR-7 request.
     * @return NanoRequest Returns the Nano request.
     *
     * @throws InvalidArgumentException for any invalid value.
     */
    public static function toNanoRequest(ServerRequestInterface $request): NanoRequest
    {
        if ($request instanceof NanoRequest) {
            return $request;
        }

        $nanoRequest = new NanoRequest(
            $request->getServerParams(),
            $request->getUploadedFiles(),
            $request->getUri(),
            $request->getMethod(),
            $request->getBody(),
            $request->getHeaders(),
            $request->getCookieParams(),
            $request->getQueryParams(),
            $request->getParsedBody(),
            $request->getProtocolVersion()
        );

        foreach ($request->getAttributes() as $name => $value) {
            $nanoRequest = $nanoRequest->withAttribute($name, $value);
        }

        return $nanoRequest;
    }

    /**
     * Transform a standard PSR-7 response into a NanoResponse.
     *
     * @param ResponseInterface $response The PSR-7 response.
     * @return NanoResponse Returns the Nano response.
     *
     * @throws InvalidArgumentException for any invalid value.
     */
    public static function toNanoResponse(ResponseInterface $response): NanoResponse
    {
        if ($response instanceof NanoResponse) {
            return $response;
        }

        return new NanoResponse(
            $response->getBody(),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }
}