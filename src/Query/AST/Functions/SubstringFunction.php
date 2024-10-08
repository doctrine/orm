<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "SUBSTRING" "(" StringPrimary "," SimpleArithmeticExpression "," SimpleArithmeticExpression ")"
 *
 * @link    www.doctrine-project.org
 */
class SubstringFunction extends FunctionNode
{
    /** @var Node */
    public $stringPrimary;

    /** @var SimpleArithmeticExpression */
    public $firstSimpleArithmeticExpression;

    /** @var SimpleArithmeticExpression|null */
    public $secondSimpleArithmeticExpression = null;

    /** @inheritDoc */
    public function getSql(SqlWalker $sqlWalker)
    {
        $optionalSecondSimpleArithmeticExpression = null;
        if ($this->secondSimpleArithmeticExpression !== null) {
            $optionalSecondSimpleArithmeticExpression = $sqlWalker->walkSimpleArithmeticExpression($this->secondSimpleArithmeticExpression);
        }

        return $sqlWalker->getConnection()->getDatabasePlatform()->getSubstringExpression(
            $sqlWalker->walkStringPrimary($this->stringPrimary),
            $sqlWalker->walkSimpleArithmeticExpression($this->firstSimpleArithmeticExpression),
            $optionalSecondSimpleArithmeticExpression
        );
    }

    /** @inheritDoc */
    public function parse(Parser $parser)
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->firstSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);

            $this->secondSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
