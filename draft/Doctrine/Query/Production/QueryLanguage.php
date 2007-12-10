<?php
/**
 * QueryLanguage = SelectStatement | UpdateStatement | DeleteStatement
 */
class Doctrine_Query_Production_QueryLanguage extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        switch ($this->_parser->lookahead['type']) {
            case Doctrine_Query_Token::T_SELECT:
            case Doctrine_Query_Token::T_FROM:
                $this->SelectStatement();
            break;
            case Doctrine_Query_Token::T_UPDATE:
                $this->UpdateStatement();
            break;
            case Doctrine_Query_Token::T_DELETE:
                $this->DeleteStatement();
            break;
            default:
                $this->_parser->syntaxError();
        }
    }
}
