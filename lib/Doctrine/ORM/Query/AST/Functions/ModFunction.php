<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

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

    /**
     * @inheritdoc
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getModExpression(
            $sqlWalker->walkSimpleArithmeticExpression($this->firstSimpleArithmeticExpression),
            $sqlWalker->walkSimpleArithmeticExpression($this->secondSimpleArithmeticExpression)
        );
    }

    /**
     * @inheritdoc
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->firstSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_COMMA);

        $this->secondSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
