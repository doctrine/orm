<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * Container for token type constants of Doctrine Query Language.
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
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
    const T_MOD                 = 142;
    const T_SIZE                = 143;

    private function __construct() {}
}
