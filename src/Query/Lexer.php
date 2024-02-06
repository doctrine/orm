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
    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_NONE = 1;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_INTEGER = 2;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_STRING = 3;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_INPUT_PARAMETER = 4;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_FLOAT = 5;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_CLOSE_PARENTHESIS = 6;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_OPEN_PARENTHESIS = 7;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_COMMA = 8;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_DIVIDE = 9;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_DOT = 10;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_EQUALS = 11;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_GREATER_THAN = 12;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_LOWER_THAN = 13;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_MINUS = 14;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_MULTIPLY = 15;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_NEGATE = 16;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_PLUS = 17;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_OPEN_CURLY_BRACE = 18;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_CLOSE_CURLY_BRACE = 19;

    // All tokens that are identifiers or keywords that could be considered as identifiers should be >= 100
    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_ALIASED_NAME = 100;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_FULLY_QUALIFIED_NAME = 101;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_IDENTIFIER = 102;

    // All keyword tokens should be >= 200
    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_ALL = 200;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_AND = 201;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_ANY = 202;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_AS = 203;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_ASC = 204;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_AVG = 205;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_BETWEEN = 206;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_BOTH = 207;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_BY = 208;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_CASE = 209;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_COALESCE = 210;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_COUNT = 211;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_DELETE = 212;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_DESC = 213;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_DISTINCT = 214;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_ELSE = 215;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_EMPTY = 216;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_END = 217;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_ESCAPE = 218;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_EXISTS = 219;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_FALSE = 220;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_FROM = 221;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_GROUP = 222;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_HAVING = 223;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_HIDDEN = 224;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_IN = 225;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_INDEX = 226;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_INNER = 227;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_INSTANCE = 228;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_IS = 229;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_JOIN = 230;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_LEADING = 231;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_LEFT = 232;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_LIKE = 233;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_MAX = 234;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_MEMBER = 235;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_MIN = 236;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_NEW = 237;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_NOT = 238;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_NULL = 239;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_NULLIF = 240;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_OF = 241;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_OR = 242;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_ORDER = 243;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_OUTER = 244;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_PARTIAL = 245;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_SELECT = 246;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_SET = 247;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_SOME = 248;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_SUM = 249;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_THEN = 250;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_TRAILING = 251;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_TRUE = 252;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_UPDATE = 253;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_WHEN = 254;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
    public const T_WHERE = 255;

    /** @deprecated use Doctrine\ORM\Query\TokenType */
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
