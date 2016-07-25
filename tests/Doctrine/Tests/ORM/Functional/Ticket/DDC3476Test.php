<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-3476
 */
class DDC3476Test extends OrmFunctionalTestCase
{
    public function testJoinTableInheritsParentTableOptions()
    {
        $em = $this->_getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $classes = array(
            $em->getClassMetadata('Doctrine\Tests\Models\DDC3476\DDC3476User'),
            $em->getClassMetadata('Doctrine\Tests\Models\DDC3476\DDC3476Group'),
        );
        $schema = $schemaTool->getSchemaFromMetadata($classes);

        $this->assertTrue($schema->hasTable('users'), 'Table users should exist.');
        $this->assertTrue($schema->hasTable('groups'), 'Table groups should exist.');
        $this->assertTrue($schema->hasTable('user_groups'), 'Table user_groups should exist.');

        $joinTable = $schema->getTable('user_groups');
        $this->assertArrayHasKey('engine', $joinTable->getOptions(), 'Join table should have option engine');
        $this->assertEquals(
            'MyISAM',
            $joinTable->getOption('engine'),
            'Option engine should be MyISAM'
        );
        $this->assertArrayHasKey('collate', $joinTable->getOptions(), 'Join table should have option collate');
        $this->assertEquals(
            'utf8_general_ci',
            $joinTable->getOption('collate'),
            'Option collate should be utf8_general_ci'
        );
    }
}
