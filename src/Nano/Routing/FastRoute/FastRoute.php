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

namespace Nano\Routing\FastRoute;

use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use Nano\Routing\FastRoute\Result;
use Psr\Container\ContainerInterface;

/**
 * Wrapper class for FastRoute routing engine.
 *
 * @see https://github.com/franksacco/nano-framework/blob/master/docs/routing.md
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class FastRoute
{
    /**
     * @var callable
     */
    private $routeDefinitionCallback;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var string
     */
    private $dispatcherClass = Dispatcher\GroupCountBased::class;

    /**
     * @var array
     */
    private $dispatchData;

    /**
     * @var array
     */
    private $reverseData;

    /**
     * @var bool
     */
    private $dataLoaded = false;

    /**
     * @var UrlGenerator|null
     */
    private $urlGenerator;

    /**
     * Initialize FastRoute engine.
     *
     * @param callable $routeDefinitionCallback The callback used to define
     *   routes; this callable expects a Router instance as unique parameter.
     * @param RouteParser|null $routeParser [optional] The route parser;
     *   default: `FastRoute\RouteParser\Std`.
     * @param DataGenerator|null $dataGenerator [optional] The data generator;
     *   default: `FastRoute\DataGenerator\GroupCountBased`.
     * @param string|null $dispatcherClass [optional] The dispatcher class name;
     *   default: `FastRoute\Dispatcher\GroupCountBased`.
     * @param ContainerInterface|null $container [optional] The DI container
     *   used to resolve middleware definitions.
     */
    public function __construct(callable $routeDefinitionCallback,
                                ?RouteParser $routeParser = null,
                                ?DataGenerator $dataGenerator = null,
                                ?string $dispatcherClass = null,
                                ?ContainerInterface $container = null)
    {
        $this->routeDefinitionCallback = $routeDefinitionCallback;
        $this->router = new Router(
            $routeParser ?: new RouteParser\Std(),
            $dataGenerator ?: new DataGenerator\GroupCountBased(),
             $container
        );

        if (is_a($dispatcherClass, Dispatcher::class, true)) {
            $this->dispatcherClass = $dispatcherClass;
        }
    }

    /**
     * Load routing data by cache or the given callback.
     *
     * @param bool $cache [optional] Whether the caching is enabled or not; default: `false`.
     * @param string $cacheDir [optional] The directory where to save cache files.
     *
     * @throws RoutingException if an error occur during data loading.
     */
    public function loadData(bool $cache = false, string $cacheDir = null)
    {
        if ($cache) {
            $cacheDir = realpath($cacheDir);
            if ($cacheDir === false) {
                throw RoutingException::forInvalidCacheDir();
            }
            $cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);
        }
        $dispatchDataFile = $cacheDir . DIRECTORY_SEPARATOR . 'dispatchData.php';
        $reverseDataFile  = $cacheDir . DIRECTORY_SEPARATOR . 'reverseData.php';

        if ($cache && file_exists($dispatchDataFile) && file_exists($reverseDataFile)) {
            $dispatchData = require $dispatchDataFile;
            $reverseData  = require $reverseDataFile;
            if (! is_array($dispatchData) || ! is_array($reverseData)) {
                throw RoutingException::forInvalidCacheFile();
            }
            $this->dispatchData = $dispatchData;
            $this->reverseData  = $reverseData;

        } else {
            ($this->routeDefinitionCallback)($this->router);

            $this->dispatchData = $this->router->getData();
            $this->reverseData  = $this->router->getReverseData();
            if ($cache) {
                file_put_contents(
                    $dispatchDataFile,
                    '<?php return ' . var_export($this->dispatchData, true) . ';'
                );
                file_put_contents(
                    $reverseDataFile,
                    '<?php return ' . var_export($this->reverseData, true) . ';'
                );
            }
        }

        $this->dataLoaded = true;
    }

    /**
     * Perform the URI dispatching.
     *
     * @param string $method The HTTP method of the server request.
     * @param string $path The path of the server request.
     * @return Result\RoutingResultInterface Returns an object representing
     *     the result of the dispatching.
     *
     * @throws RoutingException if the routing data has not yet been loaded.
     */
    public function dispatch(string $method, string $path): Result\RoutingResultInterface
    {
        if (! $this->dataLoaded) {
            throw RoutingException::forNotLoadedData();
        }

        /** @var Dispatcher $dispatcher */
        $dispatcher = new $this->dispatcherClass($this->dispatchData);
        $routeInfo  = $dispatcher->dispatch($method, $path);

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                $middlewares = $routeInfo[1]['middlewares'] ?? [];
                $handler     = $routeInfo[1]['handler'] ?? null;
                return new Result\FoundResult($middlewares, $handler, $routeInfo[2]);

            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Result\MethodNotAllowedResult($routeInfo[1]);

            case Dispatcher::NOT_FOUND:
            default:
                return new Result\NotFoundResult();
        }
    }

    /**
     * Get the url generator.
     *
     * @return UrlGenerator Returns the url generator.
     *
     * @throws RoutingException if the routing data has not yet been loaded.
     */
    public function getUrlGenerator(): UrlGenerator
    {
        if (! $this->dataLoaded) {
            throw RoutingException::forNotLoadedData();
        }

        if ($this->urlGenerator === null) {
            $this->urlGenerator = new UrlGenerator($this->reverseData);
        }
        return $this->urlGenerator;
    }
}