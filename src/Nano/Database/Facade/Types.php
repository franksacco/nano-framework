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

namespace Nano\Database\Facade;

use PDO;

/**
 * Wrapper interface for SQL data types.
 *
 * @package Nano\Database
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface Types
{
    /**
     * Represents the SQL `null` data type.
     */
    const NULL = PDO::PARAM_NULL;

    /**
     * Represents the SQL INTEGER data type.
     */
    const INT = PDO::PARAM_INT;

    /**
     * Represents the SQL CHAR, VARCHAR, or other string data type.
     */
    const STRING = PDO::PARAM_STR;

    /**
     * Represents a boolean data type.
     */
    const BOOL = PDO::PARAM_BOOL;

    /**
     * Represents the SQL large object data type.
     */
    const LOB = PDO::PARAM_LOB;

    /**
     * Represents the SQL FLOAT data type; is an alias for `Types::STRING`.
     */
    const FLOAT = self::STRING;

    /**
     * Represents the SQL TIMESTAMP; is an alias for `Types::INT`.
     */
    const TIMESTAMP = self::INT;

    /**
     * Represents the SQL TIME, DATE, or DATETIME; is an alias for `Types::STRING`.
     */
    const DATETIME = self::STRING;

    /**
     * Represents a string in JSON encoding; is an alias for `Types::STRING`.
     */
    const JSON = self::STRING;
}