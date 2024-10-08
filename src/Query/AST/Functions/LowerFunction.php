<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

use function sprintf;

/**
 * "LOWER" "(" StringPrimary ")"
 *
 * @link    www.doctrine-project.org
 */
class LowerFunction extends FunctionNode
{
    /** @var Node */
    public $stringPrimary;

    /** @inheritDoc */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            'LOWER(%s)',
            $sqlWalker->walkSimpleArithmeticExpression($this->stringPrimary)
        );
    }

    /** @inheritDoc */
    public function parse(Parser $parser)
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
