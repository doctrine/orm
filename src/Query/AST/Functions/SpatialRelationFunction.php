<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\AST\ExpressionWithReturnType;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Base class of the functions testing whether two geometries are in a spatial relationship of the OGC Simple Features
 * specification, which return a boolean.
 *
 * "ST_CONTAINS" "(" ArithmeticPrimary "," ArithmeticPrimary ")", for instance.
 */
abstract class SpatialRelationFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $firstGeometry;
    public Node|string $secondGeometry;

    abstract protected function getRelation(): SpatialRelation;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSpatialRelationExpression(
            $this->getRelation(),
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
        return Types::BOOLEAN;
    }
}
