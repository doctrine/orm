<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

use function assert;
use function reset;
use function sprintf;

/**
 * "IDENTITY" "(" SingleValuedAssociationPathExpression {"," string} ")"
 *
 * @link    www.doctrine-project.org
 */
class IdentityFunction extends FunctionNode
{
    /** @var PathExpression */
    public $pathExpression;

    /** @var string|null */
    public $fieldMapping;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        assert($this->pathExpression->field !== null);
        $entityManager = $sqlWalker->getEntityManager();
        $platform      = $entityManager->getConnection()->getDatabasePlatform();
        $quoteStrategy = $entityManager->getConfiguration()->getQuoteStrategy();
        $dqlAlias      = $this->pathExpression->identificationVariable;
        $assocField    = $this->pathExpression->field;
        $assoc         = $sqlWalker->getMetadataForDqlAlias($dqlAlias)->associationMappings[$assocField];
        $targetEntity  = $entityManager->getClassMetadata($assoc['targetEntity']);
        $joinColumn    = reset($assoc['joinColumns']);

        if ($this->fieldMapping !== null) {
            if (! isset($targetEntity->fieldMappings[$this->fieldMapping])) {
                throw new QueryException(sprintf('Undefined reference field mapping "%s"', $this->fieldMapping));
            }

            $field      = $targetEntity->fieldMappings[$this->fieldMapping];
            $joinColumn = null;

            foreach ($assoc['joinColumns'] as $mapping) {
                if ($mapping['referencedColumnName'] === $field['columnName']) {
                    $joinColumn = $mapping;

                    break;
                }
            }

            if ($joinColumn === null) {
                throw new QueryException(sprintf('Unable to resolve the reference field mapping "%s"', $this->fieldMapping));
            }
        }

        // The table with the relation may be a subclass, so get the table name from the association definition
        $tableName = $entityManager->getClassMetadata($assoc['sourceEntity'])->getTableName();

        $tableAlias = $sqlWalker->getSQLTableAlias($tableName, $dqlAlias);
        $columnName = $quoteStrategy->getJoinColumnName($joinColumn, $targetEntity, $platform);

        return $tableAlias . '.' . $columnName;
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

            $token = $parser->getLexer()->token;
            assert($token !== null);
            $this->fieldMapping = $token['value'];
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
