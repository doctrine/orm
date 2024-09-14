<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

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

    /** @inheritDoc */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            'SQRT(%s)',
            $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression)
        );
    }

    /** @inheritDoc */
    public function parse(Parser $parser)
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
