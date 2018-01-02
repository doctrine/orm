<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * Exception thrown when a Proxy fails to retrieve an Entity result.
 *
 * @author robo
 * @since 2.0
 */
class EntityNotFoundException extends ORMException
{
    /**
     * Static constructor.
     *
     * @param string   $className
     * @param string[] $id
     *
     * @return self
     */
    public static function fromClassNameAndIdentifier($className, array $id)
    {
        $ids = [];

        foreach ($id as $key => $value) {
            $ids[] = $key . '(' . $value . ')';
        }


        return new self(
            'Entity of type \'' . $className . '\'' . ($ids ? ' for IDs ' . implode(', ', $ids) : '') . ' was not found'
        );
    }
}
