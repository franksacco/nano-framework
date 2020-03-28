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

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Dispatch the request to an action in order to produce a response.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Dispatcher
{
    /**
     * The name of the attribute that define the final request handler.
     */
    const REQUEST_HANDLER_ACTION = 'request_handler_action';

    /**
     * The name of the attribute that contains handler parameters.
     */
    const REQUEST_HANDLER_PARAMS = 'request_handler_params';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Initialize the dispatcher.
     *
     * @param ContainerInterface $container The DI container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch request to a controller and an action.
     *
     * @param ServerRequestInterface $request The server request.
     * @return ResponseInterface Returns the server response.
     *
     * @throws InvalidRequestHandlerException if the request handler provided
     *     is not valid or not produces a valid response.
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $request->getAttribute(self::REQUEST_HANDLER_ACTION);
        $params  = $request->getAttribute(self::REQUEST_HANDLER_PARAMS);

        $result = $this->callHandler($handler, is_array($params) ? $params : []);

        return $this->createResponse($result);
    }

    /**
     * Execute the request handler and return the result.
     *
     * A valid `$handler` can be any of the following:
     *  - a string in the format `"class::method"`,
     *  - a string in the format `"class@method"`,
     *  - the name of a class implementing `__invoke()` method,
     *  - an instance of a class implementing `__invoke()` method,
     *  - an array in the format `["class", "method"]`,
     *  - an array in the format `[object, "method"]`,
     *  - the name of a function,
     *  - a closure (anonymous function).
     * Each method in the list above is suggested to be non-static, but it can
     * also be static.
     *
     * @param mixed $handler The callable request handler.
     * @param array $params The list of handler parameters.
     * @return mixed Returns the result of execution of the handler.
     *
     * @throws InvalidRequestHandlerException if the handler is not valid.
     */
    private function callHandler($handler, array $params)
    {
        try {
            if (is_string($handler)) {
                if (strpos($handler, '::') !== false) {
                    // Handler: "class::method"
                    $handler = explode('::', $handler);

                } else if (strpos($handler, '@') !== false) {
                    // Handler: "class@method"
                    $handler = explode('@', $handler);

                } elseif (! function_exists($handler)) {
                    // Handler: "class" (with `__invoke()` method)
                    $handler = $this->resolveClass($handler);
                }
            }

            if (is_array($handler) && isset($handler[0]) && isset($handler[1])) {
                // Handler: ["class", "method"] or [object, "method"]
                if (is_string($handler[0])) {
                    $handler[0] = $this->resolveClass($handler[0]);
                }

                $reflection = new ReflectionMethod($handler[0], $handler[1]);
                return $reflection->invokeArgs($reflection->isStatic() ? null : $handler[0], $params);
            }

            if (is_object($handler)) {
                /** @var object $handler */
                // Handler: object (with `__invoke()` method)
                $reflection = new ReflectionMethod($handler, '__invoke');
                return $reflection->invokeArgs($handler, $params);
            }

            if (is_callable($handler)) {
                // Handler: "function" or closure
                $reflection = new ReflectionFunction(\Closure::fromCallable($handler));
                return $reflection->invokeArgs($params);
            }

        } catch (ReflectionException | NotFoundExceptionInterface $e) {}

        throw InvalidRequestHandlerException::forInvalidHandler();
    }

    /**
     * Resolve a class using the DI container.
     *
     * @param string $class The name of the class.
     * @return object
     *
     * @throws InvalidRequestHandlerException if the class cannot be resolved.
     */
    private function resolveClass(string $class)
    {
        if (! $this->container->has($class)) {
            throw InvalidRequestHandlerException::forInvalidClass($class);
        }

        $result = $this->container->get($class);
        if (! is_object($result)) {
            throw InvalidRequestHandlerException::forNonObject($class);
        }

        return $result;
    }

    /**
     * Create a valid server response.
     *
     * @param ResponseInterface|string $result The result of handler execution.
     * @return ResponseInterface
     *
     * @throws InvalidRequestHandlerException if the result of the request
     *     handler is neither a ResponseInterface instance or a string.
     */
    private function createResponse($result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;

        } else if (is_string($result)) {
            return new HtmlResponse($result);
        }

        throw InvalidRequestHandlerException::forInvalidResult($result);
    }
}