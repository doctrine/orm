<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\EncryptedType;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\SqlWalker;

final class FixSqlWalker extends SqlWalker
{
    public function walkPathExpression($pathExpr): string
    {
        $sql = parent::walkPathExpression($pathExpr);

        if ($pathExpr->type === PathExpression::TYPE_STATE_FIELD) {
            $fieldName    = $pathExpr->field;
            $dqlAlias     = $pathExpr->identificationVariable;
            $class        = $this->getMetadataForDqlAlias($dqlAlias);
            $fieldMapping = $class->fieldMappings[$fieldName] ?? [];

            if (isset($fieldMapping['requireSQLConversion'])) {
                $type = Type::getType($fieldMapping['type']);
                $sql  = $type->convertToPHPValueSQL($sql, $this->getConnection()->getDatabasePlatform());
            }
        }

        return $sql;
    }
}
