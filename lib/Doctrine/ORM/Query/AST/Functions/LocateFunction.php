<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Lexer;

/**
 * "LOCATE" "(" StringPrimary "," StringPrimary ["," SimpleArithmeticExpression]")"
 *
 * 
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class LocateFunction extends FunctionNode
{
    public $firstStringPrimary;
    public $secondStringPrimary;

    /**
     * @var \Doctrine\ORM\Query\AST\SimpleArithmeticExpression|bool
     */
    public $simpleArithmeticExpression = false;

    /**
     * @override
     * @inheritdoc
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {

        return $sqlWalker->getConnection()->getDatabasePlatform()->getLocateExpression(
            $sqlWalker->walkStringPrimary($this->secondStringPrimary), // its the other way around in platform
            $sqlWalker->walkStringPrimary($this->firstStringPrimary),
            (($this->simpleArithmeticExpression)
                ? $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression)
                : false
            )
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

        $this->firstStringPrimary = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->secondStringPrimary = $parser->StringPrimary();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
