<?php
namespace Doctrine\ORM\Internal\Hydration;

class CachedResultStatement extends \ArrayIterator implements ResultStatementInterface {
    
    public function fetch($fetchStyle = PDO::FETCH_BOTH)
    {
        $value = $this->current();
        $this->next();
        return $value;
    }
  
    public function closeCursor()
    {
        $this->rewind();
        return true;
    }
  
}
