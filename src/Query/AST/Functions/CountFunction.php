<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\TypedExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Override;

/**
 * "COUNT" "(" ["DISTINCT"] StringPrimary ")"
 */
final class CountFunction extends FunctionNode implements TypedExpression
{
    private AggregateExpression $aggregateExpression;

    #[Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        return $this->aggregateExpression->dispatch($sqlWalker);
    }

    #[Override]
    public function parse(Parser $parser): void
    {
        $this->aggregateExpression = $parser->AggregateExpression();
    }

    #[Override]
    public function getReturnType(): Type
    {
        return Type::getType(Types::INTEGER);
    }
}
