<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Mock class for DatabasePlatform.
 */
class DatabasePlatformMock extends AbstractPlatform
{
    /**
     * @var string
     */
    private $sequenceNextValSql = "";

    /**
     * @var bool
     */
    private $prefersIdentityColumns = true;

    /**
     * @var bool
     */
    private $prefersSequences = false;

    /**
     * {@inheritdoc}
     */
    public function prefersIdentityColumns()
    {
        return $this->prefersIdentityColumns;
    }

    /**
     * {@inheritdoc}
     */
    public function prefersSequences()
    {
        return $this->prefersSequences;
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceNextValSQL($sequenceName)
    {
        return $this->sequenceNextValSql;
    }

    /**
     * {@inheritdoc}
     */
    public function getBooleanTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegerTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBigIntTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getVarcharTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
    }

    /* MOCK API */

    /**
     * @param bool $bool
     *
     * @return void
     */
    public function setPrefersIdentityColumns($bool)
    {
        $this->prefersIdentityColumns = $bool;
    }

    /**
     * @param bool $bool
     *
     * @return void
     */
    public function setPrefersSequences($bool)
    {
        $this->prefersSequences = $bool;
    }

    /**
     * @param string $sql
     *
     * @return void
     */
    public function setSequenceNextValSql($sql)
    {
        $this->sequenceNextValSql = $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        throw DBALException::notSupported(__METHOD__);
    }
}
