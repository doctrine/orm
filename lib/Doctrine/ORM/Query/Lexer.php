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
class Lexer
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

    private $_keywordsTable;

    /**
     * Array of scanned tokens.
     *
     * @var array
     */
    private $_tokens = array();

    /**
     * @todo Doc
     */
    private $_position = 0;

    /**
     * @todo Doc
     */
    private $_peek = 0;

    /**
     * @var array The next token in the query string.
     */
    public $lookahead;

    /**
     * @var array The last matched/seen token.
     */
    public $token;

    /**
     * Creates a new query scanner object.
     *
     * @param string $input a query string
     */
    public function __construct($input)
    {
        $this->_scan($input);
    }

    /**
     * Checks whether a given token matches the current lookahead.
     *
     * @param <type> $token
     * @return <type>
     */
    public function isNextToken($token)
    {
        $la = $this->lookahead;
        return ($la['type'] === $token || $la['value'] === $token);
    }

    /**
     * Moves to the next token in the input string.
     *
     * A token is an associative array containing three items:
     *  - 'value'    : the string value of the token in the input string
     *  - 'type'     : the type of the token (identifier, numeric, string, input
     *                 parameter, none)
     *  - 'position' : the position of the token in the input string
     *
     * @return array|null the next token; null if there is no more tokens left
     */
    public function moveNext()
    {
        $this->token = $this->lookahead;
        $this->_peek = 0;
        if (isset($this->_tokens[$this->_position])) {
            $this->lookahead = $this->_tokens[$this->_position++];
            return true;
        } else {
            $this->lookahead = null;
            return false;
        }
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, the lexer moves on to the next token, otherwise a syntax error
     * is raised.
     *
     * @param int|string token type or value
     * @return bool True, if tokens match; false otherwise.
     */
    /*public function match($token)
    {
        if (is_string($token)) {
            $isMatch = ($this->lookahead['value'] === $token);
        } else {
            $isMatch = ($this->lookahead['type'] === $token);
        }

        if ( ! $isMatch) {
            $this->syntaxError($this->getLiteral($token));
        }

        $this->moveNext();
    }*/

    /**
     * Checks if an identifier is a keyword and returns its correct type.
     *
     * @param string $identifier identifier name
     * @return int token type
     */
    public function _checkLiteral($identifier)
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
     * Scans the input string for tokens.
     *
     * @param string $input a query string
     */
    private function _scan($input)
    {
        static $regex;

        if ( ! isset($regex)) {
            $patterns = array(
                '[a-z_][a-z0-9_\\\]*',
                '(?:[0-9]+(?:[,\.][0-9]+)*)(?:e[+-]?[0-9]+)?',
                "'(?:[^']|'')*'",
                '\?[1-9]+|:[a-z][a-z0-9_]+'
            );
            $regex = '/(' . implode(')|(', $patterns) . ')|\s+|(.)/i';
        }

        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $matches = preg_split($regex, $input, -1, $flags);

        foreach ($matches as $match) {
            $value = $match[0];
            $type = $this->_getType($value);
            $this->_tokens[] = array(
                'value' => $value,
                'type'  => $type,
                'position' => $match[1]
            );
        }
    }

    /**
     * @todo Doc
     */
    private function _getType(&$value)
    {
        // $value is referenced because it can be changed if it is numeric.
        // [TODO] Revisit the _isNumeric and _getNumeric methods to reduce overhead.
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
        if ($value[0] === "'" && $value[strlen($value) - 1] === "'") {
            $type = self::T_STRING;
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

        // World number: 1.000.000,02 or -1,234e-2
        $worldnum = strtr($value, array('.' => '', ',' => '.'));
        if (is_numeric($worldnum)) {
            return $worldnum;
        }

        // American extensive number: 1,000,000.02
        $american_en = strtr($value, array(',' => ''));
        if (is_numeric($american_en)) {
            return $american_en;
        }

        return false;

    }

    /**
     * @todo Doc
     */
    public function isA($value, $token)
    {
        $type = $this->_getType($value);

        return $type === $token;
    }

    /**
     * Moves the lookahead token forward.
     *
     * @return array|null The next token or NULL if there are no more tokens ahead.
     */
    public function peek()
    {
        if (isset($this->_tokens[$this->_position + $this->_peek])) {
            return $this->_tokens[$this->_position + $this->_peek++];
        } else {
            return null;
        }
    }

    /**
     * Peeks at the next token, returns it and immediately resets the peek.
     *
     * @return array|null The next token or NULL if there are no more tokens ahead.
     */
    public function glimpse()
    {
        $peek = $this->peek();
        $this->_peek = 0;
        return $peek;
    }

    /**
     * @todo Doc
     */
    public function resetPeek()
    {
        $this->_peek = 0;
    }

    /**
     * Resets the lexer position on the input to the given position.
     */
    public function resetPosition($position = 0)
    {
        $this->_position = $position;
    }

    public function getLiteral($token)
    {
        if ( ! $this->_keywordsTable) {
            $this->_keywordsTable = array(
                self::T_ALL => "ALL",
                self::T_AND => "AND",
                self::T_ANY => "ANY",
                self::T_AS => "AS",
                self::T_ASC => "ASC",
                self::T_AVG => "AVG",
                self::T_BETWEEN => "BETWEEN",
                self::T_BY => "BY",
                self::T_COMMA => ",",
                self::T_COUNT => "COUNT",
                self::T_DELETE => "DELETE",
                self::T_DESC => "DESC",
                self::T_DISTINCT => "DISTINCT",
                self::T_DOT => ".",
                self::T_ESCAPE => "ESCAPE",
                self::T_EXISTS => "EXISTS",
                self::T_FALSE => "FALSE",
                self::T_FROM => "FROM",
                self::T_GROUP => "GROUP",
                self::T_HAVING => "HAVING",
                self::T_IN => "IN",
                self::T_INDEX => "INDEX",
                self::T_INNER => "INNER",
                self::T_IS => "IS",
                self::T_JOIN => "JOIN",
                self::T_LEFT => "LEFT",
                self::T_LIKE => "LIKE",
                self::T_LIMIT => "LIMIT",
                self::T_MAX => "MAX",
                self::T_MIN => "MIN",
                self::T_MOD => "MOD",
                self::T_NOT => "NOT",
                self::T_NULL => "NULL",
                self::T_OFFSET => "OFFSET",
                self::T_ON => "ON",
                self::T_OR => "OR",
                self::T_ORDER => "ORDER",
                self::T_OUTER => "OUTER",
                self::T_SELECT => "SELECT",
                self::T_SET => "SET",
                self::T_SIZE => "SIZE",
                self::T_SOME => "SOME",
                self::T_SUM => "SUM",
                self::T_TRUE => "TRUE",
                self::T_UPDATE => "UPDATE",
                self::T_WHERE => "WHERE",
                self::T_WITH => "WITH");
        }
        return isset($this->_keywordsTable[$token])
                ? $this->_keywordsTable[$token]
                : (is_string($token) ? $token : '');
    }
}