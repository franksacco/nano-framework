<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2020 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Controller\Helper;

use Laminas\Diactoros\Response\RedirectResponse;
use Nano\Config\ConfigurationInterface;
use Nano\Controller\Exception\UnexpectedValueException;
use Nano\Routing\FastRoute\RoutingException;
use Nano\Routing\FastRoute\UrlGenerator;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller helper for easy HTTP redirection.
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class RedirectHelper extends AbstractHelper
{
    /**
     * Perform an HTTP redirection to a route.
     *
     * @param string $route The route name.
     * @param array $params [optional] The route parameters.
     * @param int $status [optional] The response status code; default: 302.
     * @param array $headers [optional] The array of additional headers.
     * @return ResponseInterface Returns the redirection server response.
     *
     * @throws UnexpectedValueException if a valid {@see UrlGenerator} cannot
     *     be retrieved from the Dependency Injector container.
     * @throws RoutingException if an error occur during the path generation.
     */
    public function route(string $route,
                          array $params = [],
                          int $status = 302,
                          array $headers = []): ResponseInterface
    {
        $generator = $this->controller->getContainer()->get(UrlGenerator::class);
        if (! $generator instanceof UrlGenerator) {
            throw UnexpectedValueException::forInvalidUrlGenerator($generator);
        }

        $route = $generator->getPath($route, ...$params);
        return new RedirectResponse($route, $status, $headers);
    }

    /**
     * Perform an HTTP redirection to the given URL.
     *
     * If the given URL has a leading '/', it is considered as a relative path
     * and the base URL of the application, set in configuration, is added.
     *
     * @param string $url The absolute or relative URL for the redirection.
     * @param int $status [optional] The response status code; default: 302.
     * @param array $headers [optional] The array of additional headers.
     * @return ResponseInterface Returns the redirection server response.
     *
     * @throws UnexpectedValueException if a valid {@see ConfigurationInterface}
     *     cannot be retrieved from the Dependency Injector container.
     */
    public function url(string $url, int $status = 302, array $headers = []): ResponseInterface
    {
        if (substr($url, 0, 1) === '/') {
            $config = $this->controller->getContainer()->get(ConfigurationInterface::class);
            if (! $config instanceof ConfigurationInterface) {
                throw UnexpectedValueException::forInvalidConfiguration($config);
            }

            $url = $config->get('base_url') . $url;
        }

        return new RedirectResponse($url, $status, $headers);
    }
}
