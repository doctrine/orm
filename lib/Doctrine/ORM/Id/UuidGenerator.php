<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;

use function method_exists;

/**
 * Represents an ID generator that uses the database UUID expression
 *
 * @deprecated use an application-side generator instead
 */
class UuidGenerator extends AbstractIdGenerator
{
    public function __construct()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/7312',
            '%s is deprecated with no replacement, use an application-side generator instead',
            self::class
        );

        if (! method_exists(AbstractPlatform::class, 'getGuidExpression')) {
            throw NotSupported::createForDbal3();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotSupported
     */
    public function generate(EntityManager $em, $entity)
    {
        $conn = $em->getConnection();
        $sql  = 'SELECT ' . $conn->getDatabasePlatform()->getGuidExpression();

        return $conn->executeQuery($sql)->fetchOne();
    }
}
