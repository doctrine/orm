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

namespace Doctrine\Common\Annotations;

/**
 * Simple lexer for docblock annotations.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class Lexer
{
    const T_NONE = 1;
    const T_FLOAT = 2;
    const T_INTEGER = 3;
    const T_STRING = 4;
    const T_IDENTIFIER = 5;

    /**
     * Array of scanned tokens.
     *
     * @var array
     */
    private $_tokens = array();
    private $_position = 0;
    private $_peek = 0;

    /**
     * @var array The next token in the query string.
     */
    public $lookahead;

    /**
     * @var array The last matched/seen token.
     */
    public $token;
    
    public function setInput($input)
    {
        $this->_tokens = array();
        $this->_scan($input);
    }
    
    public function reset()
    {
        $this->lookahead = null;
        $this->token = null;
        $this->_peek = 0;
        $this->_position = 0;
    }

    /**
     * Checks whether a given token matches the current lookahead.
     *
     * @param integer|string $token
     * @return boolean
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
     * Tells the lexer to skip input tokens until it sees a token with the given value.
     * 
     * @param $value The value to skip until.
     */
    public function skipUntil($value)
    {
        while ($this->lookahead !== null && $this->lookahead['value'] !== $value) {
            $this->moveNext();
        }
    }

    /**
     * Checks if an identifier is a keyword and returns its correct type.
     *
     * @param string $identifier identifier name
     * @return int token type
     */
    private function _checkLiteral($identifier)
    {
        $name = 'Doctrine\Common\Annotations\Lexer::T_' . strtoupper($identifier);

        if (defined($name)) {
            return constant($name);
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
                '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?',
                '"(?:[^"]|"")*"'
            );
            $regex = '/(' . implode(')|(', $patterns) . ')|\s+|(.)/i';
        }

        $matches = preg_split($regex, $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        foreach ($matches as $match) {
            $type = $this->_getType($match);
            $this->_tokens[] = array(
                'value' => $match,
                'type'  => $type
            );
        }
    }

    /**
     * @todo Doc
     */
    private function _getType(&$value)
    {
        // $value is referenced because it can be changed if it is numeric.
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
        if ($value[0] === '"') {
            $type = self::T_STRING;
            $value = str_replace('""', '"', substr($value, 1, strlen($value) - 2));
        } else if (ctype_alpha($value[0]) || $value[0] === '_') {
            $type = $this->_checkLiteral($value);
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
}