<?php
namespace Doctrine\ORM\Internal\Hydration;

interface ResultStatementInterface
{
    
    public function fetch($fetchStyle = PDO::FETCH_BOTH);
    public function closeCursor();
  
}
