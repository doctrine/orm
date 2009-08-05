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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query;

/**
 * Scans a DQL query for tokens.
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class Lexer extends \Doctrine\Common\Lexer
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
    const T_EMPTY               = 115;
    const T_ESCAPE              = 116;
    const T_EXISTS              = 117;
    const T_FROM                = 118;
    const T_GROUP               = 119;
    const T_HAVING              = 120;
    const T_IN                  = 121;
    const T_INDEX               = 122;
    const T_INNER               = 123;
    const T_IS                  = 124;
    const T_JOIN                = 125;
    const T_LEFT                = 126;
    const T_LIKE                = 127;
    const T_LIMIT               = 128;
    const T_MAX                 = 129;
    const T_MIN                 = 130;
    const T_MOD                 = 131;
    const T_NOT                 = 132;
    const T_NULL                = 133;
    const T_OFFSET              = 134;
    const T_ON                  = 135;
    const T_OR                  = 136;
    const T_ORDER               = 137;
    const T_OUTER               = 138;
    const T_SELECT              = 139;
    const T_SET                 = 140;
    const T_SIZE                = 141;
    const T_SOME                = 142;
    const T_SUM                 = 143;
    const T_UPDATE              = 144;
    const T_WHERE               = 145;
    const T_WITH                = 146;
    const T_TRUE                = 147;
    const T_FALSE               = 148;
    const T_MEMBER              = 149;
    const T_OF                  = 150;

    private $_keywordsTable;

    /**
     * Creates a new query scanner object.
     *
     * @param string $input a query string
     */
    public function __construct($input)
    {
        $this->setInput($input);
    }

    /**
     * @inheritdoc
     */
    protected function getCatchablePatterns()
    {
        return array(
            '[a-z_][a-z0-9_\\\]*',
            '(?:[0-9]+(?:[,\.][0-9]+)*)(?:e[+-]?[0-9]+)?',
            "'(?:[^']|'')*'",
            '\?[1-9]+|:[a-z][a-z0-9_]+'
        );
    }
    
    /**
     * @inheritdoc
     */
    protected function getNonCatchablePatterns()
    {
        return array('\s+', '(.)');
    }

    /**
     * @inheritdoc
     */
    protected function _getType(&$value)
    {
        $type = self::T_NONE;

        $newVal = $this->_getNumeric($value);
        if ($newVal !== false){
            $value = $newVal;
            if (strpos($value, '.') !== false || stripos($value, 'e') !== false) {
                $type = self::T_FLOAT;
            } else {
                $type = self::T_INTEGER;
            }
        }
        if ($value[0] === "'") {
            $type = self::T_STRING;
            $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));
        } else if (ctype_alpha($value[0]) || $value[0] === '_') {
            $type = $this->_checkLiteral($value);
        } else if ($value[0] === '?' || $value[0] === ':') {
            $type = self::T_INPUT_PARAMETER;
        }

        return $type;
    }

    /**
     * @todo Doc
     */
    private function _getNumeric($value)
    {
        if ( ! is_scalar($value)) {
            return false;
        }
        // Checking for valid numeric numbers: 1.234, -1.234e-2
        if (is_numeric($value)) {
            return $value;
        }

        return false;
    }
    
    /**
     * Checks if an identifier is a keyword and returns its correct type.
     *
     * @param string $identifier identifier name
     * @return int token type
     */
    private function _checkLiteral($identifier)
    {
        $name = 'Doctrine\ORM\Query\Lexer::T_' . strtoupper($identifier);

        if (defined($name)) {
            $type = constant($name);
            if ($type > 100) {
                return $type;
            }
        }

        return self::T_IDENTIFIER;
    }

    /**
     * Gets the literal for a given token.
     *
     * @param mixed $token
     * @return string
     */
    public function getLiteral($token)
    {
        if ( ! $this->_keywordsTable) {
            $this->_keywordsTable = array(
                self::T_ALL      => "ALL",
                self::T_AND      => "AND",
                self::T_ANY      => "ANY",
                self::T_AS       => "AS",
                self::T_ASC      => "ASC",
                self::T_AVG      => "AVG",
                self::T_BETWEEN  => "BETWEEN",
                self::T_BY       => "BY",
                self::T_COMMA    => ",",
                self::T_COUNT    => "COUNT",
                self::T_DELETE   => "DELETE",
                self::T_DESC     => "DESC",
                self::T_DISTINCT => "DISTINCT",
                self::T_DOT      => ".",
                self::T_EMPTY    => "EMPTY",
                self::T_ESCAPE   => "ESCAPE",
                self::T_EXISTS   => "EXISTS",
                self::T_FALSE    => "FALSE",
                self::T_FROM     => "FROM",
                self::T_GROUP    => "GROUP",
                self::T_HAVING   => "HAVING",
                self::T_IN       => "IN",
                self::T_INDEX    => "INDEX",
                self::T_INNER    => "INNER",
                self::T_IS       => "IS",
                self::T_JOIN     => "JOIN",
                self::T_LEFT     => "LEFT",
                self::T_LIKE     => "LIKE",
                self::T_LIMIT    => "LIMIT",
                self::T_MAX      => "MAX",
                self::T_MIN      => "MIN",
                self::T_MOD      => "MOD",
                self::T_NOT      => "NOT",
                self::T_NULL     => "NULL",
                self::T_OFFSET   => "OFFSET",
                self::T_ON       => "ON",
                self::T_OR       => "OR",
                self::T_ORDER    => "ORDER",
                self::T_OUTER    => "OUTER",
                self::T_SELECT   => "SELECT",
                self::T_SET      => "SET",
                self::T_SIZE     => "SIZE",
                self::T_SOME     => "SOME",
                self::T_SUM      => "SUM",
                self::T_TRUE     => "TRUE",
                self::T_UPDATE   => "UPDATE",
                self::T_WHERE    => "WHERE",
                self::T_WITH     => "WITH");
        }
        return isset($this->_keywordsTable[$token])
                ? $this->_keywordsTable[$token]
                : (is_string($token) ? $token : '');
    }
}