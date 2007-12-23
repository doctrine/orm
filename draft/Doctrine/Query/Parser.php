<?php
class Doctrine_Query_Parser
{
    /**
     * The minimum number of tokens read after last detected error before
     * another error can be reported.
     *
     * @var int
     */
    const MIN_ERROR_DISTANCE = 2;

    /**
     * A scanner object.
     *
     * @var Doctrine_Query_Scanner
     */
    protected $_scanner;

    /**
     * An array of production objects with their names as keys.
     *
     * @var array
     */
    protected $_productions = array();

    /**
     * The next token in the query string.
     *
     * @var Doctrine_Query_Token
     */
    public $lookahead;

    /**
     * Array containing syntax and semantical errors detected in the query
     * string during parsing process.
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * The number of tokens read since last error in the input string
     *
     * @var int
     */
    protected $_errorDistance = self::MIN_ERROR_DISTANCE;

    /**
     * A query printer object used to print a parse tree from the input string.
     *
     * @var Doctrine_Query_Printer
     */
    protected $_printer;

    /**
     * Creates a new query parser object.
     *
     * @param string $input query string to be parsed
     */
    public function __construct($input)
    {
        $this->_scanner = new Doctrine_Query_Scanner($input);
        $this->_printer = new Doctrine_Query_Printer(true);
    }

    public function getProduction($name)
    {
        if ( ! isset($this->_productions[$name])) {
            $class = 'Doctrine_Query_Production_' . $name;
            $this->_productions[$name] = new $class($this);
        }

        return $this->_productions[$name];
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, updates the lookahead token; otherwise raises a syntax
     * error.
     *
     * @param int|string token type or value
     */
    public function match($token)
    {
        if (is_string($token)) {
            $isMatch = ($this->lookahead['value'] === $token);
        } else {
            $isMatch = ($this->lookahead['type'] === $token);
        }

        if ($isMatch) {
            //$this->_printer->println($this->lookahead['value']);
            $this->lookahead = $this->_scanner->next();
            $this->_errorDistance++;
        } else {
            $this->syntaxError();
        }
    }

    public function syntaxError()
    {
        $this->_error('Unexpected "' . $this->lookahead['value'] . '"');
    }

    public function semanticalError($message)
    {
        $this->_error($message);
    }

    protected function _error($message)
    {
        if ($this->_errorDistance >= self::MIN_ERROR_DISTANCE) {
            $message .= 'at line ' . $this->lookahead['line']
                 . ', column ' . $this->lookahead['column'];
            $this->_errors[] = $message;
        }

        $this->_errorDistance = 0;
    }

    /**
     * Returns the scanner object associated with this object.
     *
     * @return Doctrine_Query_Scanner
     */
    public function getScanner()
    {
        return $this->_scanner;
    }

    public function getPrinter()
    {
        return $this->_printer;
    }

    /**
     * Parses a query string.
     *
     * @throws Doctrine_Query_Parser_Exception if errors were detected in the query string
     */
    public function parse()
    {
        $this->lookahead = $this->_scanner->next();

        $this->getProduction('QueryLanguage')->execute();

        if ($this->lookahead !== null) {
            $this->_error('End of string expected.');
        }

        if (count($this->_errors)) {
            $msg = 'Query string parsing failed ('
                 . implode('; ', $this->_errors) . ').';
            throw new Doctrine_Query_Parser_Exception($msg);
        }
    }
}
