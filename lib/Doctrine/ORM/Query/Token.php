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

namespace Doctrine\ORM\Query;

/**
 * Container for token type constants of Doctrine Query Language.
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
final class Token
{
    const T_NONE                = 1;
    const T_IDENTIFIER          = 2;
    const T_INTEGER             = 3;
    const T_STRING              = 4;
    const T_INPUT_PARAMETER     = 5;
    const T_FLOAT               = 6;

    const T_ALL                 = 101;
    const T_AND                 = 102;
    const T_ANY                 = 103;
    const T_AS                  = 104;
    const T_ASC                 = 105;
    const T_AVG                 = 106;
    const T_BETWEEN             = 107;
    const T_BY                  = 108;
    const T_COMMA				= 109;
    const T_COUNT               = 110;
    const T_DELETE              = 111;
    const T_DESC                = 112;
    const T_DISTINCT            = 113;
    const T_DOT                 = 114;
    const T_ESCAPE              = 115;
    const T_EXISTS              = 116;
    const T_FROM                = 117;
    const T_GROUP               = 118;
    const T_HAVING              = 119;
    const T_IN                  = 120;
    const T_INDEX               = 121;
    const T_INNER               = 122;
    const T_IS                  = 123;
    const T_JOIN                = 124;
    const T_LEFT                = 125;
    const T_LIKE                = 126;
    const T_LIMIT               = 127;
    const T_MAX                 = 128;
    const T_MIN                 = 129;
    const T_MOD                 = 130;
    const T_NOT                 = 131;
    const T_NULL                = 132;
    const T_OFFSET              = 133;
    const T_ON                  = 134;
    const T_OR                  = 135;
    const T_ORDER               = 136;
    const T_OUTER               = 137;
    const T_SELECT              = 138;
    const T_SET                 = 139;
    const T_SIZE                = 140;
    const T_SOME                = 141;
    const T_SUM                 = 142;
    const T_UPDATE              = 143;
    const T_WHERE               = 144;
    const T_WITH                = 145;
    const T_TRUE                = 146;
    const T_FALSE               = 147;

    protected $_keywordsTable;

    public function __construct()
    {
    }

    protected function addKeyword($token, $value)
    {
        $this->_keywordsTable[$token] = $value;
    }

    public function getLiteral($token)
    {
        if ( ! $this->_keywordsTable) {
            $this->addKeyword(self::T_ALL, "ALL");
            $this->addKeyword(self::T_AND, "AND");
            $this->addKeyword(self::T_ANY, "ANY");
            $this->addKeyword(self::T_AS, "AS");
            $this->addKeyword(self::T_ASC, "ASC");
            $this->addKeyword(self::T_AVG, "AVG");
            $this->addKeyword(self::T_BETWEEN, "BETWEEN");
            $this->addKeyword(self::T_BY, "BY");
            $this->addKeyword(self::T_COMMA, ",");
            $this->addKeyword(self::T_COUNT, "COUNT");
            $this->addKeyword(self::T_DELETE, "DELETE");
            $this->addKeyword(self::T_DESC, "DESC");
            $this->addKeyword(self::T_DISTINCT, "DISTINCT");
            $this->addKeyword(self::T_DOT, ".");
            $this->addKeyword(self::T_ESCAPE, "ESPACE");
            $this->addKeyword(self::T_EXISTS, "EXISTS");
            $this->addKeyword(self::T_FALSE, "FALSE");
            $this->addKeyword(self::T_FROM, "FROM");
            $this->addKeyword(self::T_GROUP, "GROUP");
            $this->addKeyword(self::T_HAVING, "HAVING");
            $this->addKeyword(self::T_IN, "IN");
            $this->addKeyword(self::T_INDEX, "INDEX");
            $this->addKeyword(self::T_INNER, "INNER");
            $this->addKeyword(self::T_IS, "IS");
            $this->addKeyword(self::T_JOIN, "JOIN");
            $this->addKeyword(self::T_LEFT, "LEFT");
            $this->addKeyword(self::T_LIKE, "LIKE");
            $this->addKeyword(self::T_LIMIT, "LIMIT");
            $this->addKeyword(self::T_MAX, "MAX");
            $this->addKeyword(self::T_MIN, "MIN");
            $this->addKeyword(self::T_MOD, "MOD");
            $this->addKeyword(self::T_NOT, "NOT");
            $this->addKeyword(self::T_NULL, "NULL");
            $this->addKeyword(self::T_OFFSET, "OFFSET");
            $this->addKeyword(self::T_ON, "ON");
            $this->addKeyword(self::T_OR, "OR");
            $this->addKeyword(self::T_ORDER, "ORDER");
            $this->addKeyword(self::T_OUTER, "OUTER");
            $this->addKeyword(self::T_SELECT, "SELECT");
            $this->addKeyword(self::T_SET, "SET");
            $this->addKeyword(self::T_SIZE, "SIZE");
            $this->addKeyword(self::T_SOME, "SOME");
            $this->addKeyword(self::T_SUM, "SUM");
            $this->addKeyword(self::T_TRUE, "TRUE");
            $this->addKeyword(self::T_UPDATE, "UPDATE");
            $this->addKeyword(self::T_WHERE, "WHERE");
            $this->addKeyword(self::T_WITH, "WITH");
        }
        return isset($this->_keywordsTable[$token]) 
            ? $this->_keywordsTable[$token] 
            : (is_string($token) ? $token : '');
    }
}