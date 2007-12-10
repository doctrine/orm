<?php
class Doctrine_Query_Scanner
{
    /**
     * The query string
     *
     * @var string
     */
    protected $_input;

    /**
     * The length of the query string
     *
     * @var string
     */
    protected $_length;

    /**
     * Array of tokens already peeked
     *
     * @var array
     */
    protected $_tokens = array();

    /**
     *
     * @var int
     */
    protected $_peekPosition = 0;

    protected $_position = 0;
    protected $_line = 1;
    protected $_column = 1;

    protected static $_regex = array(
        'identifier'          => '/^[a-z][a-z0-9_]*/i',
        'numeric'             => '/^[+-]?([0-9]+([\.][0-9]+)?)(e[+-]?[0-9]+)?/i',
        'string'              => "/^'([^']|'')*'/",
        'input_parameter'     => '/^\?|:[a-z]+/'
    );

    /**
     * Creates a new query scanner object.
     *
     * @param string $input a query string
     */
    public function __construct($input)
    {
        $this->_input = $input;
        $this->_length = strlen($input);
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
     * Returns the next token in the input string.
     *
     * The returned token is an associative array containing the following keys:
     *     'type'     : type of the token; @see Doctrine_Query_Token::T_* constants
     *     'value'    : string value of the token in the input string
     *     'position' : start position of the token in the input string
     *     'line'     :
     *     'column'   :
     *
     * @return array the next token
     */
    protected function _nextToken()
    {
        // ignore whitespace
        while ($this->_position < $this->_length
                && ctype_space($this->_input[$this->_position])) {
            if ($this->_input[$this->_position] === "\n") {
                $this->_line++;
                $this->_column = 1;
            } else {
                $this->_column++;
            }
            $this->_position++;
        }

        if ($this->_position < $this->_length) {
            $subject = substr($this->_input, $this->_position);

            if (preg_match(self::$_regex['identifier'], $subject, $matches)) {
                $value = $matches[0];
                $type = $this->_checkLiteral($value);
            } elseif (preg_match(self::$_regex['numeric'], $subject, $matches)) {
                $value = $matches[0];
                $type = Doctrine_Query_Token::T_NUMERIC;
            } elseif (preg_match(self::$_regex['string'], $subject, $matches)) {
                $value = $matches[0];
                $type = Doctrine_Query_Token::T_STRING;
            } elseif (preg_match(self::$_regex['input_parameter'], $subject, $matches)) {
                $value = $matches[0];
                $type = Doctrine_Query_Token::T_INPUT_PARAMETER;
            } else {
                $value = $subject[0];
                $type = Doctrine_Query_Token::T_NONE;
            }
        } else {
            $value = '';
            $type = Doctrine_Query_Token::T_EOS;
        }

        $token = array(
            'type'     => $type,
            'value'    => $value,
            'position' => $this->_position,
            'line'     => $this->_line,
            'column'   => $this->_column
        );


        $increment = strlen($value);
        $this->_position += $increment;
        $this->_column += $increment;

        return $token;
    }

    /**
     * Returns the next token without removing it from the input string.
     *
     * @return array the next token
     */
    public function peek()
    {
        if ($this->_peekPosition >= count($this->_tokens)) {
            $this->_tokens[] = $this->_nextToken();
        }

        return $this->_tokens[$this->_peekPosition++];
    }

    public function resetPeek()
    {
        $this->_peekPosition = 0;
    }

    /**
     * Returns the next token in the input string.
     *
     * @return array the next token
     */
    public function scan()
    {
        if (count($this->_tokens) > 0) {
            $this->resetPeek();
            return array_shift($this->_tokens);
        } else {
            return $this->_nextToken();
        }
    }
}
