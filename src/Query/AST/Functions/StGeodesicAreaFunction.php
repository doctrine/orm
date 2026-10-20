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
 * "ST_GEODESIC_AREA" "(" ArithmeticPrimary ")"
 *
 * Returns the geodesic area of a polygon or a multi-polygon in square meters, its coordinates being interpreted as
 * longitudes and latitudes on the WGS 84 ellipsoid.
 */
class StGeodesicAreaFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $geometry;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSpatialGeodesicAreaExpression(
            $sqlWalker->walkArithmeticPrimary($this->geometry),
        );
    }

    public function parse(Parser $parser): void
    {
        SpatialFunctions::assertSupported($parser, $this->name);

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->geometry = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::FLOAT;
    }
}
