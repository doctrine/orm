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
 * "ST_BUFFER" "(" ArithmeticPrimary "," SimpleArithmeticExpression ")"
 *
 * Returns the polygon that covers the points within the given distance of a geometry, computed in the plane of its
 * coordinates, with the distance expressed in their unit.
 */
class StBufferFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $geometry;
    public Node|string $distance;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSpatialBufferExpression(
            $sqlWalker->walkArithmeticPrimary($this->geometry),
            $sqlWalker->walkSimpleArithmeticExpression($this->distance),
        );
    }

    public function parse(Parser $parser): void
    {
        SpatialFunctions::assertSupported($parser, $this->name, 'getSpatialBufferExpression');

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->geometry = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->distance = $parser->SimpleArithmeticExpression();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::GEOMETRY;
    }
}
