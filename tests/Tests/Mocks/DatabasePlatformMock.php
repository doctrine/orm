<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

use function sprintf;

/**
 * Mock class for DatabasePlatform.
 */
class DatabasePlatformMock extends AbstractPlatform
{
    public function prefersIdentityColumns(): bool
    {
        throw new BadMethodCallException(sprintf(
            'Call to deprecated method %s().',
            __METHOD__
        ));
    }

    public function supportsIdentityColumns(): bool
    {
        return true;
    }

    public function prefersSequences(): bool
    {
        throw new BadMethodCallException(sprintf(
            'Call to deprecated method %s().',
            __METHOD__
        ));
    }

    public function supportsSequences(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getSequenceNextValSQL($sequenceName)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getBooleanTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getIntegerTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getBigIntTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getVarcharTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
    }

    /* MOCK API */

    public function getName(): string
    {
        throw new BadMethodCallException(sprintf(
            'Call to deprecated method %s().',
            __METHOD__
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
    }

    /**
     * {@inheritDoc}
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
