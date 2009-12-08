<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../TestInit.php';

class SchemaToolTest extends \Doctrine\Tests\OrmTestCase
{
    public function testAddUniqueIndexForUniqueFieldAnnocation()
    {
        $em = $this->_getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $classes = array(
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsArticle'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsComment'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsEmployee'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsGroup'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
        );

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        $this->assertTrue($schema->hasTable('cms_users'));
        $this->assertTrue($schema->getTable('cms_users')->hasIndex('cms_users_username_uniq'));
        $this->assertEquals(
            array('username'),
            array_map('strtolower', $schema->getTable('cms_users')->getIndex('cms_users_username_uniq')->getColumns())
        );
    }
}