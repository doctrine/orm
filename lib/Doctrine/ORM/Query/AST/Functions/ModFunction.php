<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Lexer;

/**
 * "MOD" "(" SimpleArithmeticExpression "," SimpleArithmeticExpression ")"
 *
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class ModFunction extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\SimpleArithmeticExpression
     */
    public $firstSimpleArithmeticExpression;

    /**
     * @var \Doctrine\ORM\Query\AST\SimpleArithmeticExpression
     */
    public $secondSimpleArithmeticExpression;

    /**
     * @override
     * @inheritdoc
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getModExpression(
            $sqlWalker->walkSimpleArithmeticExpression($this->firstSimpleArithmeticExpression),
            $sqlWalker->walkSimpleArithmeticExpression($this->secondSimpleArithmeticExpression)
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

        $this->firstSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_COMMA);

        $this->secondSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
