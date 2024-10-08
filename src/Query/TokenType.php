<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

final class TokenType
{
    // All tokens that are not valid identifiers must be < 100
    public const T_NONE              = 1;
    public const T_INTEGER           = 2;
    public const T_STRING            = 3;
    public const T_INPUT_PARAMETER   = 4;
    public const T_FLOAT             = 5;
    public const T_CLOSE_PARENTHESIS = 6;
    public const T_OPEN_PARENTHESIS  = 7;
    public const T_COMMA             = 8;
    public const T_DIVIDE            = 9;
    public const T_DOT               = 10;
    public const T_EQUALS            = 11;
    public const T_GREATER_THAN      = 12;
    public const T_LOWER_THAN        = 13;
    public const T_MINUS             = 14;
    public const T_MULTIPLY          = 15;
    public const T_NEGATE            = 16;
    public const T_PLUS              = 17;
    public const T_OPEN_CURLY_BRACE  = 18;
    public const T_CLOSE_CURLY_BRACE = 19;

    // All tokens that are identifiers or keywords that could be considered as identifiers should be >= 100
    /** @deprecated No Replacement planned. */
    public const T_ALIASED_NAME         = 100;
    public const T_FULLY_QUALIFIED_NAME = 101;
    public const T_IDENTIFIER           = 102;

    // All keyword tokens should be >= 200
    public const T_ALL      = 200;
    public const T_AND      = 201;
    public const T_ANY      = 202;
    public const T_AS       = 203;
    public const T_ASC      = 204;
    public const T_AVG      = 205;
    public const T_BETWEEN  = 206;
    public const T_BOTH     = 207;
    public const T_BY       = 208;
    public const T_CASE     = 209;
    public const T_COALESCE = 210;
    public const T_COUNT    = 211;
    public const T_DELETE   = 212;
    public const T_DESC     = 213;
    public const T_DISTINCT = 214;
    public const T_ELSE     = 215;
    public const T_EMPTY    = 216;
    public const T_END      = 217;
    public const T_ESCAPE   = 218;
    public const T_EXISTS   = 219;
    public const T_FALSE    = 220;
    public const T_FROM     = 221;
    public const T_GROUP    = 222;
    public const T_HAVING   = 223;
    public const T_HIDDEN   = 224;
    public const T_IN       = 225;
    public const T_INDEX    = 226;
    public const T_INNER    = 227;
    public const T_INSTANCE = 228;
    public const T_IS       = 229;
    public const T_JOIN     = 230;
    public const T_LEADING  = 231;
    public const T_LEFT     = 232;
    public const T_LIKE     = 233;
    public const T_MAX      = 234;
    public const T_MEMBER   = 235;
    public const T_MIN      = 236;
    public const T_NEW      = 237;
    public const T_NOT      = 238;
    public const T_NULL     = 239;
    public const T_NULLIF   = 240;
    public const T_OF       = 241;
    public const T_OR       = 242;
    public const T_ORDER    = 243;
    public const T_OUTER    = 244;
    public const T_PARTIAL  = 245;
    public const T_SELECT   = 246;
    public const T_SET      = 247;
    public const T_SOME     = 248;
    public const T_SUM      = 249;
    public const T_THEN     = 250;
    public const T_TRAILING = 251;
    public const T_TRUE     = 252;
    public const T_UPDATE   = 253;
    public const T_WHEN     = 254;
    public const T_WHERE    = 255;
    public const T_WITH     = 256;

    /** @internal */
    private function __construct()
    {
    }
}
