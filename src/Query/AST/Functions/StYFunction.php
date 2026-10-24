<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\AST\ExpressionWithReturnType;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "ST_Y" "(" ArithmeticPrimary ")"
 *
 * Returns the Y coordinate of a point (its latitude in a geographic spatial reference system).
 */
class StYFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $point;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSpatialYExpression(
            $sqlWalker->walkArithmeticPrimary($this->point),
        );
    }

    public function parse(Parser $parser): void
    {
        SpatialFunctions::assertSupported($parser, $this->name);

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->point = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::FLOAT;
    }
}
