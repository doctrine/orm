<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialSetOperation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\AST\ExpressionWithReturnType;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Base class of the functions returning the geometry resulting from a set-theoretic operation between two geometries,
 * as defined by the OGC Simple Features specification.
 *
 * "ST_INTERSECTION" "(" ArithmeticPrimary "," ArithmeticPrimary ")", for instance.
 */
abstract class SpatialSetOperationFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $firstGeometry;
    public Node|string $secondGeometry;

    abstract protected function getOperation(): SpatialSetOperation;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSpatialSetOperationExpression(
            $this->getOperation(),
            $sqlWalker->walkArithmeticPrimary($this->firstGeometry),
            $sqlWalker->walkArithmeticPrimary($this->secondGeometry),
        );
    }

    public function parse(Parser $parser): void
    {
        SpatialFunctions::assertSupported($parser, $this->name, 'getSpatialSetOperationExpression');

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->firstGeometry = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->secondGeometry = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::GEOMETRY;
    }
}
