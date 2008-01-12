<?php
/**
 * IdentificationVariable = identifier
 */
class Doctrine_Query_Production_IdentificationVariable extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $token = $this->_parser->lookahead;

        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);

        /*
        if ( ! isValidIdentificationVariable($token['value'])) {
            $this->error('"' . $name . '" is not a identification variable.');
        }
        */
    }
}
