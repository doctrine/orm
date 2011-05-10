<?php
namespace Doctrine\ORM\Internal\Hydration;

class ResultStatementDecorator implements ResultStatementInterface {
  
    /** @var Statement */
    protected $_stmt;
    
    protected $result = array();
  
    public function __construct($stmt)
    {
        $this->_stmt = $stmt;
    }
  
    public function fetch($fetchStyle = PDO::FETCH_BOTH)
    {
        $row = $this->_stmt->fetch($fetchStyle);
        if ($row !== false) {
            $this->result[] = $row;
        }
        return $row;
    }
  
    public function closeCursor()
    {
        return $this->_stmt->closeCursor();
    }
    
    public function __call($name, $args) {
      return call_user_func_array(array($this->_stmt, $name), $args);
    }
    
    public function getResult() {
        return $this->result;
    }
  
}
