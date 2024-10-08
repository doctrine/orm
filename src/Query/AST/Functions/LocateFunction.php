<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "LOCATE" "(" StringPrimary "," StringPrimary ["," SimpleArithmeticExpression]")"
 *
 * @link    www.doctrine-project.org
 */
class LocateFunction extends FunctionNode
{
    /** @var Node */
    public $firstStringPrimary;

    /** @var Node */
    public $secondStringPrimary;

    /** @var SimpleArithmeticExpression|bool */
    public $simpleArithmeticExpression = false;

    /** @inheritDoc */
    public function getSql(SqlWalker $sqlWalker)
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        $firstString  = $sqlWalker->walkStringPrimary($this->firstStringPrimary);
        $secondString = $sqlWalker->walkStringPrimary($this->secondStringPrimary);

        if ($this->simpleArithmeticExpression) {
            return $platform->getLocateExpression(
                $secondString,
                $firstString,
                $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression)
            );
        }

        return $platform->getLocateExpression($secondString, $firstString);
    }

    /** @inheritDoc */
    public function parse(Parser $parser)
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->firstStringPrimary = $parser->StringPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->secondStringPrimary = $parser->StringPrimary();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);

            $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
