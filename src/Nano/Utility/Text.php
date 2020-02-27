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

namespace Nano\Utility;

/**
 * Utility for text formatting.
 *
 * @package Nano\Utility
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class Text
{
    /**
     * Converts file size from bytes to human readable string.
     *
     * @param int $size File size in bytes.
     * @param int $precision [optional] The rounding precision; default: 2.
     * @return string Returns the size in human readable string.
     */
    public static function bytesToString(int $size, int $precision = 2): string
    {
        $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $i = (int) floor(log($size,1024));
        return @round($size/pow(1024, $i), $precision) . $unit[$i];
    }

    /**
     * Converts file size from human readable string to bytes.
     *
     * @param string $size The size in human readable string like '5MB',
     *   '500 B', '50kb'.
     * @return int Returns the file size in bytes or 0 if $size is invalid.
     */
    public static function stringToBytes(string $size): int
    {
        if (ctype_digit($size)) {
            return (int) $size;
        }

        $size = strtoupper(preg_replace('/\\s/', '', $size));
        $i = array_search(substr($size, -2), ['KB', 'MB', 'GB', 'TB', 'PB']);
        if ($i !== false) {
            $size = (float) substr($size, 0, -2);
            return $size * pow(1024, $i + 1);
        }

        if (substr($size, -1) === 'B' and ctype_digit(substr($size, 0, -1))) {
            $size = substr($size, 0, -1);
            return (int) $size;
        }

        return 0;
    }

    /**
     * Returns class name without namespaces and, optionally, convention names.
     *
     * @param string $className The complete class name.
     * @param bool $removeConvention [optional] Whether to remove convention
     *   names; default: FALSE.
     * @return string
     */
    public static function className(string $className, bool $removeConvention = false): string
    {
        $split = explode('\\', $className);
        $name = end($split);

        if ($removeConvention) {
            $conventionNames = ['Class', 'Interface', 'Trait', 'Exception', 'Controller', 'View'];
            $name = str_replace($conventionNames, '', $name);
        }
        return $name;
    }

    /**
     * Transform a string from snake_case to PascalCase.
     *
     * @param string $string The string in snake_case.
     * @return string Returns the string in PascalCase.
     */
    public static function snakeToPascal(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_'));
    }

    /**
     * Transform a string from PascalCase to snake_case.
     *
     * @param string $string The string in PascalCase.
     * @return string Returns the string in snake_case.
     */
    public static function pascalToSnake(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '\\1_\\2', $string));
    }

    /**
     * Transform a string from snake_case to camelCase.
     *
     * @param string $string The string in snake_case.
     * @return string Returns the string in camelCase.
     */
    public static function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
}