<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\AST\AggregateExpression;

/**
 * "SUM" "(" ["DISTINCT"] StringPrimary ")"
 *
 * @since   2.6
 * @author  Mathew Davies <thepixeldeveloper@icloud.com>
 */
final class SumFunction extends FunctionNode
{
    /**
     * @var AggregateExpression
     */
    private $aggregateExpression;

    /**
     * @inheritDoc
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return $this->aggregateExpression->dispatch($sqlWalker);
    }

    /**
     * @inheritDoc
     */
    public function parse(Parser $parser): void
    {
        $this->aggregateExpression = $parser->AggregateExpression();
    }
}
