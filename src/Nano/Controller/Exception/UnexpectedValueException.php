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

namespace Nano\Controller\Exception;

use Nano\Config\ConfigurationInterface;
use Nano\Error\NanoExceptionInterface;
use Nano\Routing\FastRoute\UrlGenerator;

/**
 * Exception thrown when a function returns a value of invalid type.
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class UnexpectedValueException extends \UnexpectedValueException implements NanoExceptionInterface
{
    /**
     * @param mixed $config
     * @return UnexpectedValueException
     */
    public static function forInvalidConfiguration($config): UnexpectedValueException
    {
        return new self(sprintf(
            "Redirection error: expected %s instance, got %s instead",
            ConfigurationInterface::class,
            is_object($config) ? get_class($config) : gettype($config)
        ));
    }

    /**
     * @param $urlGenerator
     * @return UnexpectedValueException
     */
    public static function forInvalidUrlGenerator($urlGenerator): UnexpectedValueException
    {
        return new UnexpectedValueException(sprintf(
            "Redirection error: expected %s instance, got %s instead",
            UrlGenerator::class,
            is_object($urlGenerator) ? get_class($urlGenerator) : gettype($urlGenerator)
        ));
    }
}