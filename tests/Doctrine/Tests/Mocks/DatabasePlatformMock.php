<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Mock class for DatabasePlatform.
 */
class DatabasePlatformMock extends AbstractPlatform
{
    /** @var string */
    private $sequenceNextValSql = '';

    /** @var bool */
    private $prefersIdentityColumns = true;

    /** @var bool */
    private $prefersSequences = false;

    /**
     * {@inheritdoc}
     */
    public function prefersIdentityColumns() : bool
    {
        return $this->prefersIdentityColumns;
    }

    /**
     * {@inheritdoc}
     */
    public function prefersSequences() : bool
    {
        return $this->prefersSequences;
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceNextValSQL($sequenceName) : string
    {
        return $this->sequenceNextValSql;
    }

    /**
     * {@inheritdoc}
     */
    public function getBooleanTypeDeclarationSQL(array $columnDef) : string
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegerTypeDeclarationSQL(array $columnDef) : string
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBigIntTypeDeclarationSQL(array $columnDef) : string
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $columnDef) : string
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef) : string
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getVarcharTypeDeclarationSQL(array $columnDef) : string
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBlobTypeDeclarationSQL(array $columnDef) : string
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getClobTypeDeclarationSQL(array $columnDef) : string
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
    public function getName() : string
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentDatabaseExpression() : string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeDoctrineTypeMappings() : void
    {
    }
}
