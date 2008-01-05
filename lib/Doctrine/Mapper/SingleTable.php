<?php 

class Doctrine_Mapper_SingleTable extends Doctrine_Mapper
{
    
    public function getDiscriminatorColumn($domainClassName)
    {
        $inheritanceMap = $this->_table->getOption('inheritanceMap');
        return isset($inheritanceMap[$domainClassName]) ? $inheritanceMap[$domainClassName] : array();
    }
    
    
    
}

