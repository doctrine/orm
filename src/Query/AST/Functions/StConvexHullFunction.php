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
 * "ST_CONVEXHULL" "(" ArithmeticPrimary ")"
 *
 * Returns the convex hull of a geometry, computed in the plane of its coordinates.
 */
class StConvexHullFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $geometry;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSpatialConvexHullExpression(
            $sqlWalker->walkArithmeticPrimary($this->geometry),
        );
    }

    public function parse(Parser $parser): void
    {
        SpatialFunctions::assertSupported($parser, $this->name, 'getSpatialConvexHullExpression');

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->geometry = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::GEOMETRY;
    }
}
