<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Lexer;

/**
 * "BIT_OR" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 *
 * @link    www.doctrine-project.org
 * @since   2.2
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class BitOrFunction extends FunctionNode
{
    public $firstArithmetic;
    public $secondArithmetic;

    /**
     * @override
     * @inheritdoc
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        return $platform->getBitOrComparisonExpression(
            $this->firstArithmetic->dispatch($sqlWalker),
            $this->secondArithmetic->dispatch($sqlWalker)
        );
    }

    /**
     * @override
     * @inheritdoc
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->firstArithmetic = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondArithmetic = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
