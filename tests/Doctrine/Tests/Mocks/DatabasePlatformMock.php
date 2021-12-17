<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Mock class for DatabasePlatform.
 */
class DatabasePlatformMock extends AbstractPlatform
{
    /** @var bool */
    private $supportsIdentityColumns = true;

    /** @var bool */
    private $supportsSequences = false;

    public function prefersIdentityColumns(): bool
    {
        throw new BadMethodCallException('Call to deprecated method.');
    }

    public function supportsIdentityColumns(): bool
    {
        return $this->supportsIdentityColumns;
    }

    public function prefersSequences(): bool
    {
        throw new BadMethodCallException('Call to deprecated method.');
    }

    public function supportsSequences(): bool
    {
        return $this->supportsSequences;
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceNextValSQL($sequenceName)
    {
        return '';
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

    public function setSupportsIdentityColumns(bool $bool): void
    {
        $this->supportsIdentityColumns = $bool;
    }

    public function setSupportsSequences(bool $bool): void
    {
        $this->supportsSequences = $bool;
    }

    public function getName(): string
    {
        throw new BadMethodCallException('Call to deprecated method.');
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

    public function getCurrentDatabaseExpression(): string
    {
        throw DBALException::notSupported(__METHOD__);
    }
}
