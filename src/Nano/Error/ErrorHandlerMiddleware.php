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

namespace Nano\Error;

use ErrorException;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Nano\Config\ConfigurationInterface;
use Nano\Http\ResponseEmitterMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Middleware for error handling.
 *
 * @package Nano\Middleware
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    const ERROR_FATAL = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR
    ];

    const ERROR_WARNING = [
        E_WARNING,
        E_CORE_WARNING,
        E_COMPILE_WARNING,
        E_USER_WARNING
    ];

    const ERROR_NOTICE = [
        E_NOTICE,
        E_USER_NOTICE
    ];

    const ERROR_DEPRECATED = [
        E_DEPRECATED,
        E_USER_DEPRECATED
    ];

    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseFactoryInterface
     */
    private $factory;

    /**
     * Initialize the error handler middleware.
     *
     * @param ConfigurationInterface $config The application configuration.
     * @param LoggerInterface $logger The PSR-3 logger instance.
     * @param ResponseFactoryInterface $factory The error response factory.
     */
    public function __construct(ConfigurationInterface $config,
                                LoggerInterface $logger,
                                ResponseFactoryInterface $factory)
    {
        $this->config  = $config;
        $this->logger  = $logger;
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        error_reporting((int) $this->config->get('error.reporting', E_ALL));

        set_error_handler([$this, 'errorHandler']);
        register_shutdown_function([$this, 'fatalErrorHandler']);

        try {
            return $handler->handle($request);

        } catch (Throwable $throwable) {
            $response = $this->exceptionHandler($throwable);

            (new ResponseEmitterMiddleware())->emit(
                $response,
                (bool) $this->config->get('error.delete_buffer', true)
            );
            return $response;
        }
    }

    /**
     * Non-fatal error handler.
     *
     * If the error-handler function returns, script execution will continue
     * with the next statement after the one that caused an error.
     *
     * @param int $level The level of the error raised.
     * @param string $message The error message.
     * @param string $filename The filename that the error was raised in.
     * @param int $line The line number the error was raised at.
     * @return bool
     */
    public function errorHandler(int $level, string $message, string $filename, int $line): bool
    {
        if (in_array($level, self::ERROR_NOTICE)) {
            $type = 'NOTICE';
        } else if (in_array($level, self::ERROR_DEPRECATED)) {
            $type = 'DEPRECATED';
        } else if (in_array($level, self::ERROR_WARNING)) {
            $type = 'WARNING';
        } else {
            $type = 'UNKNOWN';
        }

        $context = [
            'type'     => $type,
            'level'    => $level,
            'message'  => $message,
            'filename' => $filename,
            'line'     => $line
        ];

        $format = (string) $this->config->get(
            'error.log_format',
            '{type}: {message} in {filename}:{line}'
        );
        $this->logger->warning($format, $context);
        return true;
    }

    /**
     * Fatal error Handler.
     *
     * This method transforms a fatal error in a {@see ErrorException}
     * and throws it in order to use the exception handler.
     *
     * @throws ErrorException
     */
    public function fatalErrorHandler()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], self::ERROR_FATAL)) {
            throw new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
        }
    }

    /**
     * Uncaught exception handler.
     *
     * @param Throwable $throwable The exception or error object that was thrown.
     * @return ResponseInterface Returns the response with information about the exception.
     */
    public function exceptionHandler(Throwable $throwable): ResponseInterface
    {
        $debug = (bool) $this->config->get('debug', false);
        try {
            $this->logger->critical(
                'Fatal error: {message}',
                ['message' => $throwable->getMessage(), 'exception' => $throwable]
            );

            if (method_exists($this->factory, 'setData')) {
                $this->factory->setData([
                    'debug'     => $debug,
                    'showTrace' => (bool) $this->config->get('error.show_trace', true),
                    'class'     => get_class($throwable),
                    'throwable' => $throwable
                ]);
            }
            return $this->factory->createResponse(500);

        } catch (Throwable $throwable) {
            return $debug ?
                new TextResponse($throwable->__toString(), 500) :
                new EmptyResponse(500);
        }
    }
}