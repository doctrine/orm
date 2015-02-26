<?php


namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\Tests\OrmTestCase;

/**
 * Doctrine\Tests\ORM\Mapping\DefaultQuoteStrategyTest
 *
 * @author Ivan Molchanov <ivan.molchanov@opensoftdev.ru>
 */
class DefaultQuoteStrategyTest extends OrmTestCase
{
    const TEST_ENTITY = 'Doctrine\Tests\Models\NonPublicSchemaJoins\User';

    public function testGetJoinTableName()
    {
        $em = $this->_getTestEntityManager();
        $metadata = $em->getClassMetadata(self::TEST_ENTITY);
        $platform = $em->getConnection()->getDatabasePlatform();
        $strategy = new DefaultQuoteStrategy();
        $tableName = $strategy->getJoinTableName($metadata->associationMappings['readers'], $metadata, $platform);
        $this->assertEquals($tableName, 'readers.author_reader');
    }
}
