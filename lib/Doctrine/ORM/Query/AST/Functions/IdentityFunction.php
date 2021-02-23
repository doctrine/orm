<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use function reset;
use function sprintf;

/**
 * "IDENTITY" "(" SingleValuedAssociationPathExpression {"," string} ")"
 */
class IdentityFunction extends FunctionNode
{
    /** @var PathExpression */
    public $pathExpression;

    /** @var string */
    public $fieldMapping;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $entityManager = $sqlWalker->getEntityManager();
        $platform      = $entityManager->getConnection()->getDatabasePlatform();
        $dqlAlias      = $this->pathExpression->identificationVariable;
        $assocField    = $this->pathExpression->field;
        $qComp         = $sqlWalker->getQueryComponent($dqlAlias);
        $class         = $qComp['metadata'];
        $association   = $class->getProperty($assocField);
        $targetEntity  = $sqlWalker->getEntityManager()->getClassMetadata($association->getTargetEntity());
        $joinColumns   = $association->getJoinColumns();
        $joinColumn    = reset($joinColumns);

        if ($this->fieldMapping !== null) {
            $property = $targetEntity->getProperty($this->fieldMapping);

            if ($property === null) {
                throw new QueryException(sprintf('Undefined reference field mapping "%s"', $this->fieldMapping));
            }

            $joinColumn = null;

            foreach ($joinColumns as $mapping) {
                if ($mapping->getReferencedColumnName() === $property->getColumnName()) {
                    $joinColumn = $mapping;

                    break;
                }
            }

            if ($joinColumn === null) {
                throw new QueryException(sprintf('Unable to resolve the reference field mapping "%s"', $this->fieldMapping));
            }
        }

        // The table with the relation may be a subclass, so get the table name from the association definition
        $sourceClass = $sqlWalker->getEntityManager()->getClassMetadata($association->getSourceEntity());
        $tableName   = $sourceClass->getTableName();

        $tableAlias       = $sqlWalker->getSQLTableAlias($tableName, $dqlAlias);
        $quotedColumnName = $platform->quoteIdentifier($joinColumn->getColumnName());

        return $tableAlias . '.' . $quotedColumnName;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->pathExpression = $parser->SingleValuedAssociationPathExpression();

        if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $parser->match(Lexer::T_STRING);

            $this->fieldMapping = $parser->getLexer()->token['value'];
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
