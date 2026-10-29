<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\AST\ExpressionWithReturnType;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

use function assert;
use function method_exists;

/**
 * "ARRAYS_OVERLAP" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether two arrays have an element in common, like the && operator of PostgreSQL: PostgreSQL arrays, or JSON
 * arrays on the other platforms.
 */
class ArraysOverlapFunction extends FunctionNode implements ExpressionWithReturnType
{
    public Node|string $firstArray;
    public Node|string $secondArray;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();
        assert(method_exists($platform, 'getArraysOverlapExpression'));

        return $platform->getArraysOverlapExpression(
            $sqlWalker->walkArithmeticPrimary($this->firstArray),
            $sqlWalker->walkArithmeticPrimary($this->secondArray),
        );
    }

    public function parse(Parser $parser): void
    {
        if (! method_exists(AbstractPlatform::class, 'getArraysOverlapExpression')) {
            $parser->semanticalError('ARRAYS_OVERLAP() requires doctrine/dbal 4.6 or newer.');
        }

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->firstArray = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->secondArray = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::BOOLEAN;
    }
}
