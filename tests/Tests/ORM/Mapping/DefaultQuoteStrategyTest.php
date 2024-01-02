<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\Tests\Models\NonPublicSchemaJoins\User as NonPublicSchemaUser;
use Doctrine\Tests\OrmTestCase;

use function assert;

/**
 * Doctrine\Tests\ORM\Mapping\DefaultQuoteStrategyTest
 */
class DefaultQuoteStrategyTest extends OrmTestCase
{
    /**
     * @group DDC-3590
     * @group 1316
     */
    public function testGetJoinTableName(): void
    {
        $em       = $this->getTestEntityManager();
        $metadata = $em->getClassMetadata(NonPublicSchemaUser::class);
        $strategy = new DefaultQuoteStrategy();
        $platform = $this->getMockForAbstractClass(AbstractPlatform::class);
        assert($platform instanceof AbstractPlatform);

        self::assertSame(
            'readers.author_reader',
            $strategy->getJoinTableName($metadata->associationMappings['readers'], $metadata, $platform)
        );
    }
}
