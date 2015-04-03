<?php

namespace Doctrine\Tests\ORM\Mapping;

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
        $metadata = $em->getClassMetadata(NonPublicSchemaUser::CLASSNAME);
        /* @var $platform \Doctrine\DBAL\Platforms\AbstractPlatform */
        $strategy = new DefaultQuoteStrategy();
        $platform = $this->getMockForAbstractClass('Doctrine\DBAL\Platforms\AbstractPlatform');

        $this->assertSame(
            'readers.author_reader',
            $strategy->getJoinTableName($metadata->associationMappings['readers'], $metadata, $platform)
        );
    }
}
