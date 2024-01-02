<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Tools\Pagination\Exception\RowNumberOverFunctionNotEnabled;

use function trim;

/**
 * RowNumberOverFunction
 *
 * Provides ROW_NUMBER() OVER(ORDER BY...) construct for use in LimitSubqueryOutputWalker
 */
class RowNumberOverFunction extends FunctionNode
{
    public OrderByClause $orderByClause;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'ROW_NUMBER() OVER(' . trim($sqlWalker->walkOrderByClause(
            $this->orderByClause,
        )) . ')';
    }

    /**
     * @throws RowNumberOverFunctionNotEnabled
     *
     * @inheritdoc
     */
    public function parse(Parser $parser): void
    {
        throw RowNumberOverFunctionNotEnabled::create();
    }
}
