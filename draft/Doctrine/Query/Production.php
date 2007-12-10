<?php
/**
 * An abstract base class that all query parser productions extend.
 */
abstract class Doctrine_Query_Production
{
    /**
     * a parser object
     *
     * @var Doctrine_Query_Parser
     */
    protected $_parser;

    /**
     * Creates a new production object.
     *
     * @param Doctrine_Query_Parser $parser a parser object
     */
    public function __construct(Doctrine_Query_Parser $parser)
    {
        $this->_parser = $parser;
    }

    protected function _isNextToken($token)
    {
        $la = $this->_parser->lookahead;
        return ($la['type'] === $token || $la['value'] === $token);
    }

    /**
     * Executes a production with specified name and parameters.
     *
     * @param string $name production name
     * @param array $params an associative array containing parameter names and
     * their values
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->_parser->getProduction($method)->execute($args);
        $this->_parser->getPrinter()->startProduction($name);
        $retval = $this->_parser->getProduction($method)->execute($args);
        $this->_parser->getPrinter()->endProduction();

        return $retval;
    }

    /**
     * Executes this production using the specified parameters.
     *
     * @param array $params an associative array containing parameter names and
     * their values
     * @return mixed
     */
    abstract public function execute(array $params = array());

    protected function _isSubquery()
    {
        $lookahead = $this->_parser->lookahead;
        $next = $this->_parser->getScanner()->peek();

        return $lookahead['value'] === '(' && $next['type'] === Doctrine_Query_Token::T_SELECT;
    }

}
