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
 * "ST_GEODESIC_DISTANCE" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Returns the geodesic distance between two geometries in meters, their coordinates being interpreted as longitudes
 * and latitudes on the WGS 84 ellipsoid.
 */
class StGeodesicDistanceFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $firstGeometry;
    public Node|string $secondGeometry;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSpatialGeodesicDistanceExpression(
            $sqlWalker->walkArithmeticPrimary($this->firstGeometry),
            $sqlWalker->walkArithmeticPrimary($this->secondGeometry),
        );
    }

    public function parse(Parser $parser): void
    {
        SpatialFunctions::assertSupported($parser, $this->name);

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->firstGeometry = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->secondGeometry = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::FLOAT;
    }
}
