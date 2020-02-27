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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Emits a response for a PHP SAPI environment.
 *
 * @package Nano\Http
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class ResponseEmitterMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $this->emit($response);
        return $response;
    }

    /**
     * Emit a response.
     *
     * Emits a response, including status line, headers, and the message body,
     * according to the environment.
     *
     * Implementations of this method may be written in such a way as to have
     * side effects, such as usage of header() or pushing output to the
     * output buffer.
     *
     * Implementations MAY raise exceptions if they are unable to emit the
     * response; e.g., if headers have already been sent.
     *
     * @param ResponseInterface $response The response to emit.
     * @param bool $deleteBuffer Delete buffered output without printing it; default TRUE.
     * @return void
     *
     * @throws RuntimeException if headers has been emitted.
     */
    public function emit(ResponseInterface $response, bool $deleteBuffer = true)
    {
        if (headers_sent($file, $line)) {
            throw new RuntimeException("Unable to emit headers. Headers sent in file=$file line=$line.");
        }

        if (ob_get_level() > 0) {
            $body = $response->getBody();
            if ($deleteBuffer || !$body->isWritable()) {
                ob_end_clean();
            } else {
                $body->seek(0, SEEK_END);
                $body->write(ob_get_clean());
            }
        }

        $this->emitHeaders($response);
        $this->emitStatusLine($response);

        $this->emitBody($response);
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is available, it, too, is emitted.
     *
     * It is important to mention that this method should be called after
     * {@see emitHeaders()} in order to prevent PHP from changing the status code of
     * the emitted response.
     *
     * @param ResponseInterface $response The response to emit.
     * @return void
     */
    private function emitStatusLine(ResponseInterface $response)
    {
        $reasonPhrase = $response->getReasonPhrase();
        $statusCode   = $response->getStatusCode();

        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        ), true, $statusCode);
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value is an array
     * with multiple values, ensures that each is sent in such a way as to create
     * aggregate headers (instead of replace the previous).
     *
     * @param ResponseInterface $response The response to emit.
     * @return void
     */
    private function emitHeaders(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name  = ucwords($header, '-');
            $first = !($name === 'Set-Cookie');

            foreach ($values as $value) {
                header(sprintf(
                    '%s: %s',
                    $name,
                    $value
                ), $first, $statusCode);
                $first = false;
            }
        }
    }

    /**
     * Emit the message body.
     *
     * @param ResponseInterface $response The response to emit.
     * @return void
     */
    private function emitBody(ResponseInterface $response)
    {
        echo $response->getBody();
    }
}