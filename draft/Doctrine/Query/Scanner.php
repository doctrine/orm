<?php
class Doctrine_Query_Scanner2
{
    /**
     * Array of scanned tokens
     *
     * @var array
     */
    protected $_tokens = array();

    protected $_nextToken = 0;

    protected $_peek = 0;


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
     * Checks if an identifier is a keyword and returns its correct type.
     *
     * @param string $identifier identifier name
     * @return int token type
     */
    public function _checkLiteral($identifier)
    {
        $name = 'Doctrine_Query_Token::T_' . strtoupper($identifier);

        if (defined($name)) {
            $type = constant($name);

            if ($type > 100) {
                return $type;
            }
        }

        return Doctrine_Query_Token::T_IDENTIFIER;
   }

    /**
     * Scans the input string for tokens.
     *
     * @param string $input a query string
     */
    protected function _scan($input)
    {
        static $regex;

        if ( ! isset($regex)) {
            $patterns = array(
                '[a-z_][a-z0-9_]*',
                '(?:[0-9]+(?:[\.][0-9]+)?)(?:e[+-]?[0-9]+)?',
                "'(?:[^']|'')*'",
                '\?|:[a-z]+'
            );
            $regex = '/(' . implode(')|(', $patterns) . ')|\s+|(.)/i';
        }

        $flags = PREG_SPLIT_NO_EMPTY| PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $matches = preg_split($regex, $input, -1, $flags);

        foreach ($matches as $match) {
            $value = $match[0];

            if (is_numeric($value)) {
                $type = Doctrine_Query_Token::T_NUMERIC;
            } elseif ($value[0] === "'" && $value[strlen($value) - 1] === "'") {
                $type = Doctrine_Query_Token::T_STRING;
            } elseif (ctype_alpha($value[0]) || $value[0] === '_') {
                $type = $this->_checkLiteral($value);
            } elseif ($value[0] === '?' || $value[0] === ':') {
                $type = Doctrine_Query_Token::T_INPUT_PARAMETER;
            } else {
                $type = Doctrine_Query_Token::T_NONE;
            }

            $this->_tokens[] = array(
                'value' => $value,
                'type'  => $type,
                'position' => $match[1]
            );
        }
    }

    public function peek()
    {
        return $this->_tokens[$this->_nextToken + $this->_peek++];
    }

    public function resetPeek()
    {
        $this->_peek = 0;
    }

    /**
     * Returns the next token in the input string.
     *
     * A token is an associative array containing three items:
     *  - 'value'    : the string value of the token in the input string
     *  - 'type'     : the type of the token (identifier, numeric, string, input
     *                 parameter, none)
     *  - 'position' : the position of the token in the input string
     *
     * @return array|null the next token; null if there is no more tokens left
     */
    public function next()
    {
        $this->_peek = 0;
        return $this->_tokens[$this->_nextToken++];
    }


}
