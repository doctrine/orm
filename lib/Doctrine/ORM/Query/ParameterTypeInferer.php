<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

/**
 * Provides an enclosed support for parameter inferring.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ParameterTypeInferer
{
    /**
     * Infers type of a given value, returning a compatible constant:
     * - Type (\Doctrine\DBAL\Types\Type::*)
     * - Connection (\Doctrine\DBAL\Connection::PARAM_*)
     *
     * @param mixed $value Parameter value.
     *
     * @return mixed Parameter type constant.
     */
    public static function inferType($value)
    {
        if (is_int($value)) {
            return Type::INTEGER;
        }

        if (is_bool($value)) {
            return Type::BOOLEAN;
        }

        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return Type::DATETIME;
        }

        if ($value instanceof \DateInterval) {
            return Type::DATEINTERVAL;
        }

        if (is_array($value)) {
            return is_int(current($value))
                ? Connection::PARAM_INT_ARRAY
                : Connection::PARAM_STR_ARRAY;
        }

        return \PDO::PARAM_STR;
    }
}
