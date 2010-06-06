<?php

namespace Doctrine\Tests\Mocks;

class DatabasePlatformMock extends \Doctrine\DBAL\Platforms\AbstractPlatform
{
    private $_sequenceNextValSql = "";
    private $_prefersIdentityColumns = true;
    private $_prefersSequences = false;

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
    public function prefersIdentityColumns()
    {
        return $this->_prefersIdentityColumns;
    }

    /**
     * @override
     */
    public function prefersSequences()
    {
        return $this->_prefersSequences;
    }

    /** @override */
    public function getSequenceNextValSQL($sequenceName)
    {
        return $this->_sequenceNextValSql;
    }

    /** @override */
    public function getBooleanTypeDeclarationSQL(array $field) {}

    /** @override */
    public function getIntegerTypeDeclarationSQL(array $field) {}

    /** @override */
    public function getBigIntTypeDeclarationSQL(array $field) {}

    /** @override */
    public function getSmallIntTypeDeclarationSQL(array $field) {}

    /** @override */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef) {}

    /** @override */
    public function getVarcharTypeDeclarationSQL(array $field) {}
    
    /** @override */
    public function getClobTypeDeclarationSQL(array $field) {}

    /* MOCK API */

    public function setPrefersIdentityColumns($bool)
    {
        $this->_prefersIdentityColumns = $bool;
    }

    public function setPrefersSequences($bool)
    {
        $this->_prefersSequences = $bool;
    }

    public function setSequenceNextValSql($sql)
    {
        $this->_sequenceNextValSql = $sql;
    }

    public function getName()
    {
        return 'mock';
    }

    protected function initializeDoctrineTypeMappings()
    {
        
    }
}