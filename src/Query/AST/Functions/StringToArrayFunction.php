<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

use function assert;
use function method_exists;

/**
 * "STRING_TO_ARRAY" "(" StringPrimary "," StringPrimary ")"
 *
 * Splits a string into an array of the substrings separated by a delimiter, like string_to_array() of PostgreSQL:
 * a PostgreSQL array, or a JSON array on the other platforms.
 */
class StringToArrayFunction extends FunctionNode
{
    public Node $string;
    public Node $delimiter;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();
        assert(method_exists($platform, 'getStringToArrayExpression'));

        return $platform->getStringToArrayExpression(
            $sqlWalker->walkStringPrimary($this->string),
            $sqlWalker->walkStringPrimary($this->delimiter),
        );
    }

    public function parse(Parser $parser): void
    {
        if (! method_exists(AbstractPlatform::class, 'getStringToArrayExpression')) {
            $parser->semanticalError('STRING_TO_ARRAY() requires doctrine/dbal 4.6 or newer.');
        }

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->string = $parser->StringPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->delimiter = $parser->StringPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
