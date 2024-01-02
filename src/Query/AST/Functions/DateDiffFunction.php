<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "DATE_DIFF" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * @link    www.doctrine-project.org
 */
class DateDiffFunction extends FunctionNode
{
    public Node $date1;
    public Node $date2;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getDateDiffExpression(
            $this->date1->dispatch($sqlWalker),
            $this->date2->dispatch($sqlWalker),
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->date1 = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->date2 = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
