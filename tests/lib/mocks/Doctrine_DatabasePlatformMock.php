<?php

class Doctrine_DatabasePlatformMock extends Doctrine_DatabasePlatform
{
    private $_prefersIdentityColumns = false;
    
    /**
     * @override
     */
    public function getNativeDeclaration(array $field) {}
    
    /**
     * @override
     */
    public function getPortableDeclaration(array $field) {}
    
    /**
     * @override
     */
    public function prefersIdentityColumns() {
        return $this->_prefersIdentityColumns;
    }
    
    /* MOCK API */
    
    public function setPrefersIdentityColumns($bool)
    {
        $this->_prefersIdentityColumns = (bool)$bool;
    }
    
}

?>