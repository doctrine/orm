<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\ORMException;

/**
 * Tools related Exceptions.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ToolsException extends ORMException
{
    /**
     * @param string     $sql
     * @param \Exception $e
     *
     * @return ToolsException
     */
    public static function schemaToolFailure($sql, \Exception $e)
    {
        return new self("Schema-Tool failed with Error '" . $e->getMessage() . "' while executing DDL: " . $sql, 0, $e);
    }
}
