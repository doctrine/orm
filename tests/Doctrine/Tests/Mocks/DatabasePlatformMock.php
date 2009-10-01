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
    public function getSequenceNextValSql($sequenceName)
    {
        return $this->_sequenceNextValSql;
    }

    /** @override */
    public function getBooleanTypeDeclarationSql(array $field) {}

    /** @override */
    public function getIntegerTypeDeclarationSql(array $field) {}

    /** @override */
    public function getBigIntTypeDeclarationSql(array $field) {}

    /** @override */
    public function getSmallIntTypeDeclarationSql(array $field) {}

    /** @override */
    protected function _getCommonIntegerTypeDeclarationSql(array $columnDef) {}

    /** @override */
    public function getVarcharTypeDeclarationSql(array $field) {}
    
    /** @override */
    public function getClobTypeDeclarationSql(array $field) {}

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
}