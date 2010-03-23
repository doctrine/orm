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
    const T_BOTH                = 108;
    const T_BY                  = 109;
    const T_CLOSE_PARENTHESIS   = 110;
    const T_COMMA               = 111;
    const T_COUNT               = 112;
    const T_DELETE              = 113;
    const T_DESC                = 114;
    const T_DISTINCT            = 115;
    const T_DIVIDE              = 116;
    const T_DOT                 = 117;
    const T_EMPTY               = 118;
    const T_EQUALS              = 119;
    const T_ESCAPE              = 120;
    const T_EXISTS              = 121;
    const T_FALSE               = 122;
    const T_FROM                = 123;
    const T_GREATER_THAN        = 124;
    const T_GROUP               = 125;
    const T_HAVING              = 126;
    const T_IN                  = 127;
    const T_INDEX               = 128;
    const T_INNER               = 129;
    const T_IS                  = 130;
    const T_JOIN                = 131;
    const T_LEADING             = 132;
    const T_LEFT                = 133;
    const T_LIKE                = 134;
    const T_LIMIT               = 135;
    const T_LOWER_THAN          = 136;
    const T_MAX                 = 137;
    const T_MEMBER              = 138;
    const T_MIN                 = 139;
    const T_MINUS               = 140;
    const T_MOD                 = 141;
    const T_MULTIPLY            = 142;
    const T_NEGATE              = 143;
    const T_NOT                 = 144;
    const T_NULL                = 145;
    const T_OF                  = 146;
    const T_OFFSET              = 147;
    const T_OPEN_PARENTHESIS    = 149;
    const T_OR                  = 150;
    const T_ORDER               = 151;
    const T_OUTER               = 152;
    const T_PLUS                = 153;
    const T_SELECT              = 154;
    const T_SET                 = 155;
    const T_SIZE                = 156;
    const T_SOME                = 157;
    const T_SUM                 = 158;
    const T_TRAILING            = 159;
    const T_TRUE                = 160;
    const T_UPDATE              = 161;
    const T_WHERE               = 162;
    const T_WITH                = 163;
    const T_PARTIAL             = 164;
    const T_OPEN_CURLY_BRACE    = 165;
    const T_CLOSE_CURLY_BRACE   = 166;

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
            '[a-z_][a-z0-9_\:\\\]*[a-z0-9_]{1}',
            '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?',
            "'(?:[^']|'')*'",
            '\?[1-9][0-9]*|:[a-z][a-z0-9_]+'
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
        
        // Recognizing numeric values
        if ($newVal !== false){
            $value = $newVal;

            return (strpos($value, '.') !== false || stripos($value, 'e') !== false) 
                    ? self::T_FLOAT : self::T_INTEGER;
        }
        
        if ($value[0] === "'") {
            $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));
            
            return self::T_STRING;
        } else if (ctype_alpha($value[0]) || $value[0] === '_') {
            return $this->_checkLiteral($value);
        } else if ($value[0] === '?' || $value[0] === ':') {
            return self::T_INPUT_PARAMETER;
        } else {
            switch ($value) {
                case '.': return self::T_DOT;
                case ',': return self::T_COMMA;
                case '(': return self::T_OPEN_PARENTHESIS;
                case ')': return self::T_CLOSE_PARENTHESIS;
                case '=': return self::T_EQUALS;
                case '>': return self::T_GREATER_THAN;
                case '<': return self::T_LOWER_THAN;
                case '+': return self::T_PLUS;
                case '-': return self::T_MINUS;
                case '*': return self::T_MULTIPLY;
                case '/': return self::T_DIVIDE;
                case '!': return self::T_NEGATE;
                case '{': return self::T_OPEN_CURLY_BRACE;
                case '}': return self::T_CLOSE_CURLY_BRACE;
                default:
                    // Do nothing
                    break;
            }
        }

        return $type;
    }

    /**
     * @todo Inline this method.
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
     * @todo Inline this method.
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
}