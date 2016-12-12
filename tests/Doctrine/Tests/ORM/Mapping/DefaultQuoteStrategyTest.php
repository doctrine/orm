<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\Tests\Models\NonPublicSchemaJoins\User as NonPublicSchemaUser;
use Doctrine\Tests\OrmTestCase;

/**
 * Doctrine\Tests\ORM\Mapping\DefaultQuoteStrategyTest
 *
 * @author Ivan Molchanov <ivan.molchanov@opensoftdev.ru>
 */
class DefaultQuoteStrategyTest extends OrmTestCase
{
    /**
     * @group DDC-3590
     * @group 1316
     */
    public function testGetJoinTableName()
    {
        $em       = $this->_getTestEntityManager();
        $metadata = $em->getClassMetadata(NonPublicSchemaUser::class);
        $strategy = new DefaultQuoteStrategy();
        /* @var $platform AbstractPlatform */
        $platform = $this->getMockForAbstractClass(AbstractPlatform::class);

        $this->assertSame(
            'readers.author_reader',
            $strategy->getJoinTableName($metadata->associationMappings['readers'], $metadata, $platform)
        );
    }
}
