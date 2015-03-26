<?php
/**
 * RowNumberOverFunction.php
 * Created by William Schaller
 * Date: 3/27/2015
 * Time: 11:31 AM
 */
namespace Doctrine\ORM\Tools\Pagination;


use Doctrine\ORM\Query\AST\Functions\FunctionNode;

class RowNumberOverFunction extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\OrderByClause
     */
    public $orderByClause;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'ROW_NUMBER() OVER(' . trim($sqlWalker->walkOrderByClause(
            $this->orderByClause
        )) . ')';
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {}
}
