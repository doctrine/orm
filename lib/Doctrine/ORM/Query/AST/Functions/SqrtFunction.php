<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

use function sprintf;

/**
 * "SQRT" "(" SimpleArithmeticExpression ")"
 *
 * @link    www.doctrine-project.org
 */
class SqrtFunction extends FunctionNode
{
    /** @var SimpleArithmeticExpression */
    public $simpleArithmeticExpression;

    /**
     * @inheritdoc
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            'SQRT(%s)',
            $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression)
        );
    }

    /**
     * @inheritdoc
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
