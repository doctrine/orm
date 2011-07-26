<?php
namespace Doctrine\ORM\Internal\Hydration;

/**
 * 
 * This is mocking a PDO connection, used for caching
 * @author Bogdan Albei <bogdan.albei@gmail.com>
 *
 */
class PDOMock
{
    private $data;
    private $index = 0;
    
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function fetch($mode)
    {
        if(isset($this->data[$this->index])) {
            $ret = $this->data[$this->index];
            $this->index++;
            return $ret;
        }
        else {
            return FALSE;
        }
    }
    
    public function fetchAll($mode)
    {
        return $this->data;
    }
    
    public function closeCursor() 
    {
    }
}