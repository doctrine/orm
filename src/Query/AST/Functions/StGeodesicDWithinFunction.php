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
 * "ST_GEODESIC_DWITHIN" "(" ArithmeticPrimary "," ArithmeticPrimary "," SimpleArithmeticExpression ")"
 *
 * Tests whether the geodesic distance between two geometries, as computed by ST_GEODESIC_DISTANCE(), is less than or
 * equal to the given distance in meters.
 */
class StGeodesicDWithinFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $firstGeometry;
    public Node|string $secondGeometry;
    public Node|string $distance;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSpatialGeodesicWithinDistanceExpression(
            $sqlWalker->walkArithmeticPrimary($this->firstGeometry),
            $sqlWalker->walkArithmeticPrimary($this->secondGeometry),
            $sqlWalker->walkSimpleArithmeticExpression($this->distance),
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

        $parser->match(TokenType::T_COMMA);

        $this->distance = $parser->SimpleArithmeticExpression();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::BOOLEAN;
    }
}
