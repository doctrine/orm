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
    public const T_NONE = 1;

    /** @deprecated use {@see TokenType::T_INTEGER} */
    public const T_INTEGER = 2;

    /** @deprecated use {@see TokenType::T_STRING} */
    public const T_STRING = 3;

    /** @deprecated use {@see TokenType::T_INPUT_PARAMETER} */
    public const T_INPUT_PARAMETER = 4;

    /** @deprecated use {@see TokenType::T_FLOAT} */
    public const T_FLOAT = 5;

    /** @deprecated use {@see TokenType::T_CLOSE_PARENTHESIS} */
    public const T_CLOSE_PARENTHESIS = 6;

    /** @deprecated use {@see TokenType::T_OPEN_PARENTHESIS} */
    public const T_OPEN_PARENTHESIS = 7;

    /** @deprecated use {@see TokenType::T_COMMA} */
    public const T_COMMA = 8;

    /** @deprecated use {@see TokenType::T_DIVIDE} */
    public const T_DIVIDE = 9;

    /** @deprecated use {@see TokenType::T_DOT} */
    public const T_DOT = 10;

    /** @deprecated use {@see TokenType::T_EQUALS} */
    public const T_EQUALS = 11;

    /** @deprecated use {@see TokenType::T_GREATER_THAN} */
    public const T_GREATER_THAN = 12;

    /** @deprecated use {@see TokenType::T_LOWER_THAN} */
    public const T_LOWER_THAN = 13;

    /** @deprecated use {@see TokenType::T_MINUS} */
    public const T_MINUS = 14;

    /** @deprecated use {@see TokenType::T_MULTIPLY} */
    public const T_MULTIPLY = 15;

    /** @deprecated use {@see TokenType::T_NEGATE} */
    public const T_NEGATE = 16;

    /** @deprecated use {@see TokenType::T_PLUS} */
    public const T_PLUS = 17;

    /** @deprecated use {@see TokenType::T_OPEN_CURLY_BRACE} */
    public const T_OPEN_CURLY_BRACE = 18;

    /** @deprecated use {@see TokenType::T_CLOSE_CURLY_BRACE} */
    public const T_CLOSE_CURLY_BRACE = 19;

    // All tokens that are identifiers or keywords that could be considered as identifiers should be >= 100
    /** @deprecated use {@see TokenType::T_ALIASED_NAME} */
    public const T_ALIASED_NAME = 100;

    /** @deprecated use {@see TokenType::T_FULLY_QUALIFIED_NAME} */
    public const T_FULLY_QUALIFIED_NAME = 101;

    /** @deprecated use {@see TokenType::T_IDENTIFIER} */
    public const T_IDENTIFIER = 102;

    // All keyword tokens should be >= 200
    /** @deprecated use {@see TokenType::T_ALL} */
    public const T_ALL = 200;

    /** @deprecated use {@see TokenType::T_AND} */
    public const T_AND = 201;

    /** @deprecated use {@see TokenType::T_ANY} */
    public const T_ANY = 202;

    /** @deprecated use {@see TokenType::T_AS} */
    public const T_AS = 203;

    /** @deprecated use {@see TokenType::T_ASC} */
    public const T_ASC = 204;

    /** @deprecated use {@see TokenType::T_AVG} */
    public const T_AVG = 205;

    /** @deprecated use {@see TokenType::T_BETWEEN} */
    public const T_BETWEEN = 206;

    /** @deprecated use {@see TokenType::T_BOTH} */
    public const T_BOTH = 207;

    /** @deprecated use {@see TokenType::T_BY} */
    public const T_BY = 208;

    /** @deprecated use {@see TokenType::T_CASE} */
    public const T_CASE = 209;

    /** @deprecated use {@see TokenType::T_COALESCE} */
    public const T_COALESCE = 210;

    /** @deprecated use {@see TokenType::T_COUNT} */
    public const T_COUNT = 211;

    /** @deprecated use {@see TokenType::T_DELETE} */
    public const T_DELETE = 212;

    /** @deprecated use {@see TokenType::T_DESC} */
    public const T_DESC = 213;

    /** @deprecated use {@see TokenType::T_DISTINCT} */
    public const T_DISTINCT = 214;

    /** @deprecated use {@see TokenType::T_ELSE} */
    public const T_ELSE = 215;

    /** @deprecated use {@see TokenType::T_EMPTY} */
    public const T_EMPTY = 216;

    /** @deprecated use {@see TokenType::T_END} */
    public const T_END = 217;

    /** @deprecated use {@see TokenType::T_ESCAPE} */
    public const T_ESCAPE = 218;

    /** @deprecated use {@see TokenType::T_EXISTS} */
    public const T_EXISTS = 219;

    /** @deprecated use {@see TokenType::T_FALSE} */
    public const T_FALSE = 220;

    /** @deprecated use {@see TokenType::T_FROM} */
    public const T_FROM = 221;

    /** @deprecated use {@see TokenType::T_GROUP} */
    public const T_GROUP = 222;

    /** @deprecated use {@see TokenType::T_HAVING} */
    public const T_HAVING = 223;

    /** @deprecated use {@see TokenType::T_HIDDEN} */
    public const T_HIDDEN = 224;

    /** @deprecated use {@see TokenType::T_IN} */
    public const T_IN = 225;

    /** @deprecated use {@see TokenType::T_INDEX} */
    public const T_INDEX = 226;

    /** @deprecated use {@see TokenType::T_INNER} */
    public const T_INNER = 227;

    /** @deprecated use {@see TokenType::T_INSTANCE} */
    public const T_INSTANCE = 228;

    /** @deprecated use {@see TokenType::T_IS} */
    public const T_IS = 229;

    /** @deprecated use {@see TokenType::T_JOIN} */
    public const T_JOIN = 230;

    /** @deprecated use {@see TokenType::T_LEADING} */
    public const T_LEADING = 231;

    /** @deprecated use {@see TokenType::T_LEFT} */
    public const T_LEFT = 232;

    /** @deprecated use {@see TokenType::T_LIKE} */
    public const T_LIKE = 233;

    /** @deprecated use {@see TokenType::T_MAX} */
    public const T_MAX = 234;

    /** @deprecated use {@see TokenType::T_MEMBER} */
    public const T_MEMBER = 235;

    /** @deprecated use {@see TokenType::T_MIN} */
    public const T_MIN = 236;

    /** @deprecated use {@see TokenType::T_NEW} */
    public const T_NEW = 237;

    /** @deprecated use {@see TokenType::T_NOT} */
    public const T_NOT = 238;

    /** @deprecated use {@see TokenType::T_NULL} */
    public const T_NULL = 239;

    /** @deprecated use {@see TokenType::T_NULLIF} */
    public const T_NULLIF = 240;

    /** @deprecated use {@see TokenType::T_OF} */
    public const T_OF = 241;

    /** @deprecated use {@see TokenType::T_OR} */
    public const T_OR = 242;

    /** @deprecated use {@see TokenType::T_ORDER} */
    public const T_ORDER = 243;

    /** @deprecated use {@see TokenType::T_OUTER} */
    public const T_OUTER = 244;

    /** @deprecated use {@see TokenType::T_PARTIAL} */
    public const T_PARTIAL = 245;

    /** @deprecated use {@see TokenType::T_SELECT} */
    public const T_SELECT = 246;

    /** @deprecated use {@see TokenType::T_SET} */
    public const T_SET = 247;

    /** @deprecated use {@see TokenType::T_SOME} */
    public const T_SOME = 248;

    /** @deprecated use {@see TokenType::T_SUM} */
    public const T_SUM = 249;

    /** @deprecated use {@see TokenType::T_THEN} */
    public const T_THEN = 250;

    /** @deprecated use {@see TokenType::T_TRAILING} */
    public const T_TRAILING = 251;

    /** @deprecated use {@see TokenType::T_TRUE} */
    public const T_TRUE = 252;

    /** @deprecated use {@see TokenType::T_UPDATE} */
    public const T_UPDATE = 253;

    /** @deprecated use {@see TokenType::T_WHEN} */
    public const T_WHEN = 254;

    /** @deprecated use {@see TokenType::T_WHERE} */
    public const T_WHERE = 255;

    /** @deprecated use {@see TokenType::T_WITH} */
    public const T_WITH = 256;

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
        $type = self::T_NONE;

        switch (true) {
            // Recognize numeric values
            case is_numeric($value):
                if (str_contains($value, '.') || stripos($value, 'e') !== false) {
                    return self::T_FLOAT;
                }

                return self::T_INTEGER;

            // Recognize quoted strings
            case $value[0] === "'":
                $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));

                return self::T_STRING;

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

                    return self::T_ALIASED_NAME;
                }

                if (str_contains($value, '\\')) {
                    return self::T_FULLY_QUALIFIED_NAME;
                }

                return self::T_IDENTIFIER;

            // Recognize input parameters
            case $value[0] === '?' || $value[0] === ':':
                return self::T_INPUT_PARAMETER;

            // Recognize symbols
            case $value === '.':
                return self::T_DOT;

            case $value === ',':
                return self::T_COMMA;

            case $value === '(':
                return self::T_OPEN_PARENTHESIS;

            case $value === ')':
                return self::T_CLOSE_PARENTHESIS;

            case $value === '=':
                return self::T_EQUALS;

            case $value === '>':
                return self::T_GREATER_THAN;

            case $value === '<':
                return self::T_LOWER_THAN;

            case $value === '+':
                return self::T_PLUS;

            case $value === '-':
                return self::T_MINUS;

            case $value === '*':
                return self::T_MULTIPLY;

            case $value === '/':
                return self::T_DIVIDE;

            case $value === '!':
                return self::T_NEGATE;

            case $value === '{':
                return self::T_OPEN_CURLY_BRACE;

            case $value === '}':
                return self::T_CLOSE_CURLY_BRACE;

            // Default
            default:
                // Do nothing
        }

        return $type;
    }
}
