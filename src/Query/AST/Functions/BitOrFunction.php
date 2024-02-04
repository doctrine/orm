<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "BIT_OR" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * @link    www.doctrine-project.org
 */
class BitOrFunction extends FunctionNode
{
    /** @var Node */
    public $firstArithmetic;

    /** @var Node */
    public $secondArithmetic;

    /** @inheritDoc */
    public function getSql(SqlWalker $sqlWalker)
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        return $platform->getBitOrComparisonExpression(
            $this->firstArithmetic->dispatch($sqlWalker),
            $this->secondArithmetic->dispatch($sqlWalker)
        );
    }

    /** @inheritDoc */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->firstArithmetic = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondArithmetic = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
