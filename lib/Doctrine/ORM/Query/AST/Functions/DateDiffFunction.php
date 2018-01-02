<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

/**
 * "DATE_DIFF" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * 
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class DateDiffFunction extends FunctionNode
{
    public $date1;
    public $date2;

    /**
     * @override
     * @inheritdoc
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getDateDiffExpression(
            $this->date1->dispatch($sqlWalker),
            $this->date2->dispatch($sqlWalker)
        );
    }

    /**
     * @override
     * @inheritdoc
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->date1 = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->date2 = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
