<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use function sprintf;

/**
 * "SIZE" "(" CollectionValuedPathExpression ")"
 */
class SizeFunction extends FunctionNode
{
    /** @var PathExpression */
    public $collectionPathExpression;

    /**
     * @override
     * @inheritdoc
     * @todo If the collection being counted is already joined, the SQL can be simpler (more efficient).
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $platform          = $sqlWalker->getEntityManager()->getConnection()->getDatabasePlatform();
        $dqlAlias          = $this->collectionPathExpression->identificationVariable;
        $assocField        = $this->collectionPathExpression->field;
        $sql               = 'SELECT COUNT(*) FROM ';
        $qComp             = $sqlWalker->getQueryComponent($dqlAlias);
        $class             = $qComp['metadata'];
        $association       = $class->getProperty($assocField);
        $targetClass       = $sqlWalker->getEntityManager()->getClassMetadata($association->getTargetEntity());
        $owningAssociation = $association->isOwningSide()
            ? $association
            : $targetClass->getProperty($association->getMappedBy());

        if ($association instanceof OneToManyAssociationMetadata) {
            $targetTableName  = $targetClass->table->getQuotedQualifiedName($platform);
            $targetTableAlias = $sqlWalker->getSQLTableAlias($targetClass->getTableName());
            $sourceTableAlias = $sqlWalker->getSQLTableAlias($class->getTableName(), $dqlAlias);

            $sql .= $targetTableName . ' ' . $targetTableAlias . ' WHERE ';

            $owningAssociation = $targetClass->getProperty($association->getMappedBy());
            $first             = true;

            foreach ($owningAssociation->getJoinColumns() as $joinColumn) {
                if ($first) {
                    $first = false;
                } else {
                    $sql .= ' AND ';
                }

                $sql .= sprintf(
                    '%s.%s = %s.%s',
                    $targetTableAlias,
                    $platform->quoteIdentifier($joinColumn->getColumnName()),
                    $sourceTableAlias,
                    $platform->quoteIdentifier($joinColumn->getReferencedColumnName())
                );
            }
        } else { // many-to-many
            $joinTable     = $owningAssociation->getJoinTable();
            $joinTableName = $joinTable->getQuotedQualifiedName($platform);

            // SQL table aliases
            $joinTableAlias   = $sqlWalker->getSQLTableAlias($joinTable->getName());
            $sourceTableAlias = $sqlWalker->getSQLTableAlias($class->getTableName(), $dqlAlias);

            // Quote in case source table alias matches class table name (happens in an UPDATE statement)
            if ($sourceTableAlias === $class->getTableName()) {
                $sourceTableAlias = $platform->quoteIdentifier($sourceTableAlias);
            }

            // join to target table
            $sql .= $joinTableName . ' ' . $joinTableAlias . ' WHERE ';

            $joinColumns = $association->isOwningSide()
                ? $joinTable->getJoinColumns()
                : $joinTable->getInverseJoinColumns();

            $first = true;

            foreach ($joinColumns as $joinColumn) {
                if ($first) {
                    $first = false;
                } else {
                    $sql .= ' AND ';
                }

                $sql .= sprintf(
                    '%s.%s = %s.%s',
                    $joinTableAlias,
                    $platform->quoteIdentifier($joinColumn->getColumnName()),
                    $sourceTableAlias,
                    $platform->quoteIdentifier($joinColumn->getReferencedColumnName())
                );
            }
        }

        return '(' . $sql . ')';
    }

    /**
     * @override
     * @inheritdoc
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->collectionPathExpression = $parser->CollectionValuedPathExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
