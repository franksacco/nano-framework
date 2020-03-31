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

use Nano\Config\ConfigurationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware for buffering and compressing output.
 *
 * @package Nano\Http
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class BufferOutputMiddleware implements MiddlewareInterface
{
    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * BufferOutputMiddleware constructor.
     *
     * @param ConfigurationInterface $config The application configuration.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config->withPrefix('output');
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $compressionEnabled = $this->config->get('compression', false) &&
            extension_loaded('zlib') &&
            strpos($request->getHeaderLine('Accept-Encoding'), 'gzip') !== false &&
            ini_get('zlib.output_compression') !== '1';

        if ($compressionEnabled) {
            if ($this->config->get('transparent_compression', true)) {
                ini_set('zlib.output_compression', '1');
                ob_start();

            } else {
                ob_start('ob_gzhandler');
            }

        } else {
            ob_start();
        }

        $response = $handler->handle($request);

        // Append buffered output directly on the server response.
        $body = $response->getBody();
        if ($body->isWritable()) {
            $body->seek(0, SEEK_END);
            $body->write(ob_get_clean());
        }

        return $response;
    }
}