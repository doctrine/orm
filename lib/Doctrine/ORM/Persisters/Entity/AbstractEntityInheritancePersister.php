<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Entity;

use Doctrine\ORM\Mapping\JoinColumnMetadata;
use function sprintf;

/**
 * Base class for entity persisters that implement a certain inheritance mapping strategy.
 * All these persisters are assumed to use a discriminator column to discriminate entity
 * types in the hierarchy.
 */
abstract class AbstractEntityInheritancePersister extends BasicEntityPersister
{
    /**
     * {@inheritdoc}
     *
     * @param object $entity
     *
     * @return mixed[]
     */
    protected function prepareInsertData($entity) : array
    {
        $data = parent::prepareInsertData($entity);

        // Populate the discriminator column
        $discColumn = $this->class->discriminatorColumn;
        $tableName  = $discColumn->getTableName();
        $columnName = $discColumn->getColumnName();

        $this->columns[$columnName] = $discColumn;

        $data[$tableName][$columnName] = $this->class->discriminatorValue;

        return $data;
    }

    /**
     * @return string
     */
    protected function getSelectJoinColumnSQL(JoinColumnMetadata $joinColumnMetadata)
    {
        $tableAlias       = $this->getSQLTableAlias($joinColumnMetadata->getTableName());
        $columnAlias      = $this->getSQLColumnAlias();
        $columnType       = $joinColumnMetadata->getType();
        $quotedColumnName = $this->platform->quoteIdentifier($joinColumnMetadata->getColumnName());
        $sql              = sprintf('%s.%s', $tableAlias, $quotedColumnName);

        $this->currentPersisterContext->rsm->addMetaResult(
            'r',
            $columnAlias,
            $joinColumnMetadata->getColumnName(),
            $joinColumnMetadata->isPrimaryKey(),
            $columnType
        );

        return $columnType->convertToPHPValueSQL($sql, $this->platform) . ' AS ' . $columnAlias;
    }
}
