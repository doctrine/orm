<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

enum TokenType: int
{
    // All tokens that are not valid identifiers must be < 100
    case T_NONE              = 1;
    case T_INTEGER           = 2;
    case T_STRING            = 3;
    case T_INPUT_PARAMETER   = 4;
    case T_FLOAT             = 5;
    case T_CLOSE_PARENTHESIS = 6;
    case T_OPEN_PARENTHESIS  = 7;
    case T_COMMA             = 8;
    case T_DIVIDE            = 9;
    case T_DOT               = 10;
    case T_EQUALS            = 11;
    case T_GREATER_THAN      = 12;
    case T_LOWER_THAN        = 13;
    case T_MINUS             = 14;
    case T_MULTIPLY          = 15;
    case T_NEGATE            = 16;
    case T_PLUS              = 17;
    case T_OPEN_CURLY_BRACE  = 18;
    case T_CLOSE_CURLY_BRACE = 19;

    // All tokens that are identifiers or keywords that could be considered as identifiers should be >= 100
    case T_FULLY_QUALIFIED_NAME = 101;
    case T_IDENTIFIER           = 102;

    // All keyword tokens should be >= 200
    case T_ALL      = 200;
    case T_AND      = 201;
    case T_ANY      = 202;
    case T_AS       = 203;
    case T_ASC      = 204;
    case T_AVG      = 205;
    case T_BETWEEN  = 206;
    case T_BOTH     = 207;
    case T_BY       = 208;
    case T_CASE     = 209;
    case T_COALESCE = 210;
    case T_COUNT    = 211;
    case T_DELETE   = 212;
    case T_DESC     = 213;
    case T_DISTINCT = 214;
    case T_ELSE     = 215;
    case T_EMPTY    = 216;
    case T_END      = 217;
    case T_ESCAPE   = 218;
    case T_EXISTS   = 219;
    case T_FALSE    = 220;
    case T_FROM     = 221;
    case T_GROUP    = 222;
    case T_HAVING   = 223;
    case T_HIDDEN   = 224;
    case T_IN       = 225;
    case T_INDEX    = 226;
    case T_INNER    = 227;
    case T_INSTANCE = 228;
    case T_IS       = 229;
    case T_JOIN     = 230;
    case T_LEADING  = 231;
    case T_LEFT     = 232;
    case T_LIKE     = 233;
    case T_MAX      = 234;
    case T_MEMBER   = 235;
    case T_MIN      = 236;
    case T_NEW      = 237;
    case T_NOT      = 238;
    case T_NULL     = 239;
    case T_NULLIF   = 240;
    case T_OF       = 241;
    case T_OR       = 242;
    case T_ORDER    = 243;
    case T_OUTER    = 244;
    case T_PARTIAL  = 245;
    case T_SELECT   = 246;
    case T_SET      = 247;
    case T_SOME     = 248;
    case T_SUM      = 249;
    case T_THEN     = 250;
    case T_TRAILING = 251;
    case T_TRUE     = 252;
    case T_UPDATE   = 253;
    case T_WHEN     = 254;
    case T_WHERE    = 255;
    case T_WITH     = 256;
    case T_NAMED    = 257;
    case T_ON       = 258;
}
