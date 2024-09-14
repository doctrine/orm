<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "MOD" "(" SimpleArithmeticExpression "," SimpleArithmeticExpression ")"
 *
 * @link    www.doctrine-project.org
 */
class ModFunction extends FunctionNode
{
    /** @var SimpleArithmeticExpression */
    public $firstSimpleArithmeticExpression;

    /** @var SimpleArithmeticExpression */
    public $secondSimpleArithmeticExpression;

    /** @inheritDoc */
    public function getSql(SqlWalker $sqlWalker)
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getModExpression(
            $sqlWalker->walkSimpleArithmeticExpression($this->firstSimpleArithmeticExpression),
            $sqlWalker->walkSimpleArithmeticExpression($this->secondSimpleArithmeticExpression)
        );
    }

    /** @inheritDoc */
    public function parse(Parser $parser)
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->firstSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(TokenType::T_COMMA);

        $this->secondSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
