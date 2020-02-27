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
use League\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
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
    const REQUEST_HANDLER_ATTRIBUTE = 'request-handler';

    /**
     * The name of the attribute that contains handler parameters.
     */
    const HANDLER_PARAMS_ATTRIBUTE = 'handler-params';

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
        $handler = $request->getAttribute(self::REQUEST_HANDLER_ATTRIBUTE);
        $params  = $request->getAttribute(self::HANDLER_PARAMS_ATTRIBUTE);

        if (! is_callable($handler)) {
            throw new InvalidRequestHandlerException('Request handler must be callable');
        }

        $result = $this->callHandler($handler, is_array($params) ? $params : []);

        return $this->createResponse($result);
    }

    /**
     * Execute the request handler and return the result.
     *
     * @param callable $handler The callable request handler.
     * @param array $params The list of handler parameters.
     * @return mixed Returns the result of execution of the handler.
     *
     * @throws InvalidRequestHandlerException if the handler is not valid.
     */
    private function callHandler($handler, array $params)
    {
        try {
            if (is_string($handler) && strpos($handler, '::') !== false) {
                $handler = explode('::', $handler);
            }

            if (is_array($handler) && isset($handler[0]) && isset($handler[1])) {
                if (is_string($handler[0])) {
                    $handler[0] = $this->container->get($handler[0]);
                }

                $reflection = new ReflectionMethod($handler[0], $handler[1]);

                if ($reflection->isStatic()) {
                    $handler[0] = null;
                }
                return $reflection->invokeArgs($handler[0], $params);
            }

            if (is_object($handler)) {
                /** @var object $handler */
                $reflection = new ReflectionMethod($handler, '__invoke');
                return $reflection->invokeArgs($handler, $params);
            }

            $reflection = new ReflectionFunction(\Closure::fromCallable($handler));
            return $reflection->invokeArgs($params);

        } catch (ReflectionException | NotFoundException $e) {
            throw new InvalidRequestHandlerException('Invalid request handler provided');
        }
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

        throw new InvalidRequestHandlerException(sprintf(
            "The request handler must produce a %s or a string, got %s instead",
            ResponseInterface::class,
            is_object($result) ? get_class($result) : gettype($result)
        ));
    }
}