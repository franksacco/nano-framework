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

namespace Nano\Routing;

use Nano\Config\ConfigurationInterface;
use Nano\Container\Container;
use Nano\Routing\FastRoute\FastRoute;
use Nano\Routing\FastRoute\Result;
use Nano\Routing\FastRoute\Router;
use Nano\Routing\FastRoute\UrlGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;
use Twig\TwigFunction;

/**
 * Wrapper middleware for FastRoute library.
 *
 * Routing middleware can be configured through the `routing` key in
 * application settings.
 * Available options for this class are:
 *  - `cache`: whether the caching is enabled or not; default: FALSE.
 *  - `cache_dir`: the directory where to save cache files.
 *
 * @see https://github.com/nikic/FastRoute
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AbstractRoutingMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * @var ResponseFactoryInterface
     */
    private $factory;

    /**
     * @var FastRoute
     */
    private $fastRoute;

    /**
     * Initialize the routing middleware.
     *
     * @param ContainerInterface $container The DI container.
     * @param ConfigurationInterface $config The application settings.
     * @param ResponseFactoryInterface $factory The factory used to create a
     *     404 or 405 error response.
     */
    public function __construct(ContainerInterface $container,
                                ConfigurationInterface $config,
                                ResponseFactoryInterface $factory)
    {
        $this->container = $container;
        $this->config    = $config;
        $this->factory   = $factory;

        $this->fastRoute = new FastRoute(
            [$this, 'routing'],
            null,
            null,
            null,
            $container
        );

        $this->fastRoute->loadData(
            (bool) $this->config->get('routing.cache', false),
            (string) $this->config->get('routing.cache_dir', '')
        );

        $urlGenerator = $this->fastRoute->getUrlGenerator();
        if ($this->container instanceof Container) {
            $this->container->share(UrlGenerator::class,  $urlGenerator);
        }

        // Add Twig function for url generation.
        if ($this->container->has(Environment::class)) {
            /** @var Environment $twig */
            $twig = $this->container->get(Environment::class);
            $twig->addFunction(new TwigFunction('url', [$urlGenerator, 'getPath']));
        }
    }

    /**
     * Define application routes.
     *
     * Routes will run in the order they are defined. Higher routes
     * will always take precedence over lower ones.
     *
     * @param Router $router The router instance.
     */
    abstract public function routing(Router $router);

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Transform the request path in order to have always a leading '/'
        // and never a trailing '/'. In this way, the path "/example" and
        // "/example/" are the same.
        $path = '/' . trim(rawurldecode($request->getUri()->getPath()), '/');
        $result = $this->fastRoute->dispatch($request->getMethod(), $path);

        if ($result instanceof Result\FoundResult) {
            $handler = new RouteRequestHandler($result->getMiddlewares(), $handler);
            $request = $request
                ->withAttribute(Dispatcher::REQUEST_HANDLER_ATTRIBUTE, $result->getHandler())
                ->withAttribute(Dispatcher::HANDLER_PARAMS_ATTRIBUTE, $result->getParams());

        } elseif ($result instanceof Result\MethodNotAllowedResult) {
            return $this->factory->createResponse(405)
                ->withHeader('Allow', implode(',', $result->getAllowedMethods()));

        } elseif ($result instanceof Result\NotFoundResult) {
            return $this->factory->createResponse(404);
        }

        return $handler->handle($request);
    }
}