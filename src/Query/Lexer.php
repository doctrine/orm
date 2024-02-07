<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\Common\Lexer\AbstractLexer;
use Doctrine\Deprecations\Deprecation;

use function constant;
use function ctype_alpha;
use function defined;
use function is_numeric;
use function str_contains;
use function str_replace;
use function stripos;
use function strlen;
use function strtoupper;
use function substr;

/**
 * Scans a DQL query for tokens.
 *
 * @extends AbstractLexer<TokenType::T_*, string>
 */
class Lexer extends AbstractLexer
{
    // All tokens that are not valid identifiers must be < 100
    /** @deprecated use {@see TokenType::T_NONE} */
    public const T_NONE = TokenType::T_NONE;

    /** @deprecated use {@see TokenType::T_INTEGER} */
    public const T_INTEGER = TokenType::T_INTEGER;

    /** @deprecated use {@see TokenType::T_STRING} */
    public const T_STRING = TokenType::T_STRING;

    /** @deprecated use {@see TokenType::T_INPUT_PARAMETER} */
    public const T_INPUT_PARAMETER = TokenType::T_INPUT_PARAMETER;

    /** @deprecated use {@see TokenType::T_FLOAT} */
    public const T_FLOAT = TokenType::T_FLOAT;

    /** @deprecated use {@see TokenType::T_CLOSE_PARENTHESIS} */
    public const T_CLOSE_PARENTHESIS = TokenType::T_CLOSE_PARENTHESIS;

    /** @deprecated use {@see TokenType::T_OPEN_PARENTHESIS} */
    public const T_OPEN_PARENTHESIS = TokenType::T_OPEN_PARENTHESIS;

    /** @deprecated use {@see TokenType::T_COMMA} */
    public const T_COMMA = TokenType::T_COMMA;

    /** @deprecated use {@see TokenType::T_DIVIDE} */
    public const T_DIVIDE = TokenType::T_DIVIDE;

    /** @deprecated use {@see TokenType::T_DOT} */
    public const T_DOT = TokenType::T_DOT;

    /** @deprecated use {@see TokenType::T_EQUALS} */
    public const T_EQUALS = TokenType::T_EQUALS;

    /** @deprecated use {@see TokenType::T_GREATER_THAN} */
    public const T_GREATER_THAN = TokenType::T_GREATER_THAN;

    /** @deprecated use {@see TokenType::T_LOWER_THAN} */
    public const T_LOWER_THAN = TokenType::T_LOWER_THAN;

    /** @deprecated use {@see TokenType::T_MINUS} */
    public const T_MINUS = TokenType::T_MINUS;

    /** @deprecated use {@see TokenType::T_MULTIPLY} */
    public const T_MULTIPLY = TokenType::T_MULTIPLY;

    /** @deprecated use {@see TokenType::T_NEGATE} */
    public const T_NEGATE = TokenType::T_NEGATE;

    /** @deprecated use {@see TokenType::T_PLUS} */
    public const T_PLUS = TokenType::T_PLUS;

    /** @deprecated use {@see TokenType::T_OPEN_CURLY_BRACE} */
    public const T_OPEN_CURLY_BRACE = TokenType::T_OPEN_CURLY_BRACE;

    /** @deprecated use {@see TokenType::T_CLOSE_CURLY_BRACE} */
    public const T_CLOSE_CURLY_BRACE = TokenType::T_CLOSE_CURLY_BRACE;

    // All tokens that are identifiers or keywords that could be considered as identifiers should be >= 100
    /** @deprecated No Replacement planned. */
    public const T_ALIASED_NAME = TokenType::T_ALIASED_NAME;

    /** @deprecated use {@see TokenType::T_FULLY_QUALIFIED_NAME} */
    public const T_FULLY_QUALIFIED_NAME = TokenType::T_FULLY_QUALIFIED_NAME;

    /** @deprecated use {@see TokenType::T_IDENTIFIER} */
    public const T_IDENTIFIER = TokenType::T_IDENTIFIER;

    // All keyword tokens should be >= 200
    /** @deprecated use {@see TokenType::T_ALL} */
    public const T_ALL = TokenType::T_ALL;

    /** @deprecated use {@see TokenType::T_AND} */
    public const T_AND = TokenType::T_AND;

    /** @deprecated use {@see TokenType::T_ANY} */
    public const T_ANY = TokenType::T_ANY;

    /** @deprecated use {@see TokenType::T_AS} */
    public const T_AS = TokenType::T_AS;

    /** @deprecated use {@see TokenType::T_ASC} */
    public const T_ASC = TokenType::T_ASC;

    /** @deprecated use {@see TokenType::T_AVG} */
    public const T_AVG = TokenType::T_AVG;

    /** @deprecated use {@see TokenType::T_BETWEEN} */
    public const T_BETWEEN = TokenType::T_BETWEEN;

    /** @deprecated use {@see TokenType::T_BOTH} */
    public const T_BOTH = TokenType::T_BOTH;

    /** @deprecated use {@see TokenType::T_BY} */
    public const T_BY = TokenType::T_BY;

    /** @deprecated use {@see TokenType::T_CASE} */
    public const T_CASE = TokenType::T_CASE;

    /** @deprecated use {@see TokenType::T_COALESCE} */
    public const T_COALESCE = TokenType::T_COALESCE;

    /** @deprecated use {@see TokenType::T_COUNT} */
    public const T_COUNT = TokenType::T_COUNT;

    /** @deprecated use {@see TokenType::T_DELETE} */
    public const T_DELETE = TokenType::T_DELETE;

    /** @deprecated use {@see TokenType::T_DESC} */
    public const T_DESC = TokenType::T_DESC;

    /** @deprecated use {@see TokenType::T_DISTINCT} */
    public const T_DISTINCT = TokenType::T_DISTINCT;

    /** @deprecated use {@see TokenType::T_ELSE} */
    public const T_ELSE = TokenType::T_ELSE;

    /** @deprecated use {@see TokenType::T_EMPTY} */
    public const T_EMPTY = TokenType::T_EMPTY;

    /** @deprecated use {@see TokenType::T_END} */
    public const T_END = TokenType::T_END;

    /** @deprecated use {@see TokenType::T_ESCAPE} */
    public const T_ESCAPE = TokenType::T_ESCAPE;

    /** @deprecated use {@see TokenType::T_EXISTS} */
    public const T_EXISTS = TokenType::T_EXISTS;

    /** @deprecated use {@see TokenType::T_FALSE} */
    public const T_FALSE = TokenType::T_FALSE;

    /** @deprecated use {@see TokenType::T_FROM} */
    public const T_FROM = TokenType::T_FROM;

    /** @deprecated use {@see TokenType::T_GROUP} */
    public const T_GROUP = TokenType::T_GROUP;

    /** @deprecated use {@see TokenType::T_HAVING} */
    public const T_HAVING = TokenType::T_HAVING;

    /** @deprecated use {@see TokenType::T_HIDDEN} */
    public const T_HIDDEN = TokenType::T_HIDDEN;

    /** @deprecated use {@see TokenType::T_IN} */
    public const T_IN = TokenType::T_IN;

    /** @deprecated use {@see TokenType::T_INDEX} */
    public const T_INDEX = TokenType::T_INDEX;

    /** @deprecated use {@see TokenType::T_INNER} */
    public const T_INNER = TokenType::T_INNER;

    /** @deprecated use {@see TokenType::T_INSTANCE} */
    public const T_INSTANCE = TokenType::T_INSTANCE;

    /** @deprecated use {@see TokenType::T_IS} */
    public const T_IS = TokenType::T_IS;

    /** @deprecated use {@see TokenType::T_JOIN} */
    public const T_JOIN = TokenType::T_JOIN;

    /** @deprecated use {@see TokenType::T_LEADING} */
    public const T_LEADING = TokenType::T_LEADING;

    /** @deprecated use {@see TokenType::T_LEFT} */
    public const T_LEFT = TokenType::T_LEFT;

    /** @deprecated use {@see TokenType::T_LIKE} */
    public const T_LIKE = TokenType::T_LIKE;

    /** @deprecated use {@see TokenType::T_MAX} */
    public const T_MAX = TokenType::T_MAX;

    /** @deprecated use {@see TokenType::T_MEMBER} */
    public const T_MEMBER = TokenType::T_MEMBER;

    /** @deprecated use {@see TokenType::T_MIN} */
    public const T_MIN = TokenType::T_MIN;

    /** @deprecated use {@see TokenType::T_NEW} */
    public const T_NEW = TokenType::T_NEW;

    /** @deprecated use {@see TokenType::T_NOT} */
    public const T_NOT = TokenType::T_NOT;

    /** @deprecated use {@see TokenType::T_NULL} */
    public const T_NULL = TokenType::T_NULL;

    /** @deprecated use {@see TokenType::T_NULLIF} */
    public const T_NULLIF = TokenType::T_NULLIF;

    /** @deprecated use {@see TokenType::T_OF} */
    public const T_OF = TokenType::T_OF;

    /** @deprecated use {@see TokenType::T_OR} */
    public const T_OR = TokenType::T_OR;

    /** @deprecated use {@see TokenType::T_ORDER} */
    public const T_ORDER = TokenType::T_ORDER;

    /** @deprecated use {@see TokenType::T_OUTER} */
    public const T_OUTER = TokenType::T_OUTER;

    /** @deprecated use {@see TokenType::T_PARTIAL} */
    public const T_PARTIAL = TokenType::T_PARTIAL;

    /** @deprecated use {@see TokenType::T_SELECT} */
    public const T_SELECT = TokenType::T_SELECT;

    /** @deprecated use {@see TokenType::T_SET} */
    public const T_SET = TokenType::T_SET;

    /** @deprecated use {@see TokenType::T_SOME} */
    public const T_SOME = TokenType::T_SOME;

    /** @deprecated use {@see TokenType::T_SUM} */
    public const T_SUM = TokenType::T_SUM;

    /** @deprecated use {@see TokenType::T_THEN} */
    public const T_THEN = TokenType::T_THEN;

    /** @deprecated use {@see TokenType::T_TRAILING} */
    public const T_TRAILING = TokenType::T_TRAILING;

    /** @deprecated use {@see TokenType::T_TRUE} */
    public const T_TRUE = TokenType::T_TRUE;

    /** @deprecated use {@see TokenType::T_UPDATE} */
    public const T_UPDATE = TokenType::T_UPDATE;

    /** @deprecated use {@see TokenType::T_WHEN} */
    public const T_WHEN = TokenType::T_WHEN;

    /** @deprecated use {@see TokenType::T_WHERE} */
    public const T_WHERE = TokenType::T_WHERE;

    /** @deprecated use {@see TokenType::T_WITH} */
    public const T_WITH = TokenType::T_WITH;

    /**
     * Creates a new query scanner object.
     *
     * @param string $input A query string.
     */
    public function __construct($input)
    {
        $this->setInput($input);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCatchablePatterns()
    {
        return [
            '[a-z_][a-z0-9_]*\:[a-z_][a-z0-9_]*(?:\\\[a-z_][a-z0-9_]*)*', // aliased name
            '[a-z_\\\][a-z0-9_]*(?:\\\[a-z_][a-z0-9_]*)*', // identifier or qualified name
            '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?', // numbers
            "'(?:[^']|'')*'", // quoted strings
            '\?[0-9]*|:[a-z_][a-z0-9_]*', // parameters
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getNonCatchablePatterns()
    {
        return ['\s+', '--.*', '(.)'];
    }

    /**
     * {@inheritDoc}
     *
     * @param string $value
     */
    protected function getType(&$value)
    {
        $type = TokenType::T_NONE;

        switch (true) {
            // Recognize numeric values
            case is_numeric($value):
                if (str_contains($value, '.') || stripos($value, 'e') !== false) {
                    return TokenType::T_FLOAT;
                }

                return TokenType::T_INTEGER;

            // Recognize quoted strings
            case $value[0] === "'":
                $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));

                return TokenType::T_STRING;

            // Recognize identifiers, aliased or qualified names
            case ctype_alpha($value[0]) || $value[0] === '_' || $value[0] === '\\':
                $name = 'Doctrine\ORM\Query\TokenType::T_' . strtoupper($value);

                if (defined($name)) {
                    $type = constant($name);

                    if ($type > 100) {
                        return $type;
                    }
                }

                if (str_contains($value, ':')) {
                    Deprecation::trigger(
                        'doctrine/orm',
                        'https://github.com/doctrine/orm/issues/8818',
                        'Short namespace aliases such as "%s" are deprecated and will be removed in Doctrine ORM 3.0.',
                        $value
                    );

                    return TokenType::T_ALIASED_NAME;
                }

                if (str_contains($value, '\\')) {
                    return TokenType::T_FULLY_QUALIFIED_NAME;
                }

                return TokenType::T_IDENTIFIER;

            // Recognize input parameters
            case $value[0] === '?' || $value[0] === ':':
                return TokenType::T_INPUT_PARAMETER;

            // Recognize symbols
            case $value === '.':
                return TokenType::T_DOT;

            case $value === ',':
                return TokenType::T_COMMA;

            case $value === '(':
                return TokenType::T_OPEN_PARENTHESIS;

            case $value === ')':
                return TokenType::T_CLOSE_PARENTHESIS;

            case $value === '=':
                return TokenType::T_EQUALS;

            case $value === '>':
                return TokenType::T_GREATER_THAN;

            case $value === '<':
                return TokenType::T_LOWER_THAN;

            case $value === '+':
                return TokenType::T_PLUS;

            case $value === '-':
                return TokenType::T_MINUS;

            case $value === '*':
                return TokenType::T_MULTIPLY;

            case $value === '/':
                return TokenType::T_DIVIDE;

            case $value === '!':
                return TokenType::T_NEGATE;

            case $value === '{':
                return TokenType::T_OPEN_CURLY_BRACE;

            case $value === '}':
                return TokenType::T_CLOSE_CURLY_BRACE;

            // Default
            default:
                // Do nothing
        }

        return $type;
    }
}
