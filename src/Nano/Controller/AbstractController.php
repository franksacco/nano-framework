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

namespace Nano\Controller;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequestFactory;
use League\Container\Container;
use Nano\Controller\Exception\RenderingException;
use Nano\Controller\Helper\AbstractHelper;
use Nano\Controller\Exception\HelperNotFoundException;
use Nano\Controller\Exception\InvalidHelperException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract controller definition.
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AbstractController
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var callable
     */
    private $responseFactory;

    /**
     * @var ResponseInterface|null
     */
    private $response;

    /**
     * @var AbstractHelper[]
     */
    private $helpers = [];

    /**
     * Initialize the controller.
     *
     * @param ContainerInterface $container The DI container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request   = $container->has(ServerRequestInterface::class) ?
            $container->get(ServerRequestInterface::class) :
            ServerRequestFactory::fromGlobals();

        if ($container instanceof Container) {
            $container->add(AbstractController::class, $this);
        }

        $this->setResponseFactory(function () {
            return new EmptyResponse();
        });

        $this->beforeAction();
    }

    /**
     * Retrieve the DI container.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Retrieve the server request.
     *
     * @return ServerRequestInterface Returns the server request.
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Set the response factory callback.
     *
     * This function expects no parameters and must return a
     * {@see ResponseInterface} instance.
     *
     * @param callable $responseFactory The response factory callback.
     */
    public function setResponseFactory(callable $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Retrieve the server response if set.
     *
     * @return ResponseInterface|null Returns the server response or NULL.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Logic to be done on startup, i.e. before action execution.
     */
    protected function beforeAction() {}

    /**
     * Logic to be done before view rendering.
     */
    protected function beforeRender() {}

    /**
     * Render the controller creating a server response.
     *
     * @return ResponseInterface Returns the server response.
     */
    final public function render(): ResponseInterface
    {
        $this->beforeRender();

        $response = ($this->responseFactory)();
        if (! $response instanceof ResponseInterface) {
            throw new RenderingException(sprintf(
                "Response factory callback must return a %s instance, got %s instead",
                ResponseInterface::class,
                is_object($response) ? get_class($response) : gettype($response)
            ));
        }
        $this->response = $response;

        $this->afterRender($this->response);
        return $this->response;
    }

    /**
     * Logic to be done after view rendering.
     *
     * @param ResponseInterface $response The server response.
     */
    protected function afterRender(ResponseInterface $response) {}

    /**
     * Add a controller helper.
     *
     * When a class name is provided for the second parameter, the
     * helper is resolved using the dependency injection container.
     *
     * @param string $name The helper name used to refer to it.
     * @param AbstractHelper|string $helper The helper instance or class name.
     * @return self Returns self reference for methods chaining.
     *
     * @throws InvalidHelperException if the helper does not extends
     *     {@see AbstractHelper} class.
     */
    public function addHelper(string $name, $helper)
    {
        if (is_string($helper)) {
            $helper = $this->container->get($helper);
        }

        if (! $helper instanceof AbstractHelper) {
            throw new InvalidHelperException(sprintf(
                'A controller helper must extends %s', AbstractHelper::class
            ));
        }

        $this->helpers[$name] = $helper;
        return $this;
    }

    /**
     * Retrieve a controller helper by his name.
     *
     * @param string $name The controller helper name.
     * @return AbstractHelper Returns the controller helper instance.
     *
     * @throws HelperNotFoundException if the helper is not found.
     */
    public function getHelper(string $name): AbstractHelper
    {
        if (! isset($this->helpers[$name])) {
            throw new HelperNotFoundException(sprintf(
                'Helper "%s" not found in controller %s',
                $name, __CLASS__
            ));
        }

        return $this->helpers[$name];
    }

    /**
     * Checks if a controller helper is set.
     *
     * @param string $name The controller helper name.
     * @return bool Returns TRUE if the helper exists, FALSE otherwise.
     */
    public function hasHelper(string $name): bool
    {
        return isset($this->helpers[$name]);
    }

    /**
     * Retrieve a controller helper by his name.
     *
     * Proxy method for {@see AbstractController::getHelper()}.
     *
     * @param string $name The controller helper name.
     * @return AbstractHelper Returns the controller helper instance.
     *
     * @throws HelperNotFoundException if the helper is not found.
     */
    public function __get(string $name): AbstractHelper
    {
        return $this->getHelper($name);
    }

    /**
     * Checks if a controller helper is set.
     *
     * Proxy method for {@see AbstractController::hasHelper()}.
     *
     * @param string $name The controller helper name.
     * @return bool Returns TRUE if the helper exists, FALSE otherwise.
     */
    public function __isset(string $name): bool
    {
        return $this->hasHelper($name);
    }
}