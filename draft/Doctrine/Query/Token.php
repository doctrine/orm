<?php
final class Doctrine_Query_Token
{
    const T_NONE                = 1;
    const T_IDENTIFIER          = 2;
    const T_NUMERIC             = 3;
    const T_STRING              = 4;
    const T_INPUT_PARAMETER     = 5;

    const T_ALL                 = 101;
    const T_AND                 = 102;
    const T_ANY                 = 103;
    const T_AS                  = 104;
    const T_ASC                 = 105;
    const T_AVG                 = 106;
    const T_BETWEEN             = 107;
    const T_BY                  = 108;
    const T_COUNT               = 109;
    const T_DELETE              = 100;
    const T_DESC                = 111;
    const T_DISTINCT            = 112;
    const T_ESCAPE              = 113;
    const T_EXISTS              = 114;
    const T_FROM                = 115;
    const T_GROUP               = 116;
    const T_HAVING              = 117;
    const T_IN                  = 118;
    const T_INNER               = 119;
    const T_IS                  = 120;
    const T_JOIN                = 121;
    const T_LEFT                = 122;
    const T_LIKE                = 123;
    const T_LIMIT               = 124;
    const T_MAX                 = 125;
    const T_MIN                 = 126;
    const T_NOT                 = 127;
    const T_NULL                = 128;
    const T_OFFSET              = 129;
    const T_OR                  = 130;
    const T_ORDER               = 131;
    const T_SELECT              = 132;
    const T_SET                 = 133;
    const T_SOME                = 134;
    const T_SUM                 = 135;
    const T_UPDATE              = 136;
    const T_WHERE               = 137;
    const T_LENGTH              = 138;
    const T_LOCATE              = 139;
    const T_ABS                 = 140;
    const T_SQRT                = 141;
    const T_MOD                 = 142;
    const T_SIZE                = 143;

    private function __construct() {}
}
