<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\JoinColumnMetadata;

/**
 * Base class for entity persisters that implement a certain inheritance mapping strategy.
 * All these persisters are assumed to use a discriminator column to discriminate entity
 * types in the hierarchy.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.0
 */
abstract class AbstractEntityInheritancePersister extends BasicEntityPersister
{
    /**
     * {@inheritdoc}
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
     * @param JoinColumnMetadata $joinColumnMetadata
     *
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
