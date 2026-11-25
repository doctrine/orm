<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

use function sprintf;

/**
 * "SIN" "(" SimpleArithmeticExpression ")"
 *
 * Returns the sine of an angle in radians.
 */
class SinFunction extends FunctionNode
{
    public Node|string $simpleArithmeticExpression;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'SIN(%s)',
            $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression),
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
