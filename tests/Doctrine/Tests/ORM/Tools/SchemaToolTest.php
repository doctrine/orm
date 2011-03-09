<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

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

        $this->assertTrue($schema->hasTable('cms_users'), "Table cms_users should exist.");
        $this->assertTrue($schema->getTable('cms_users')->columnsAreIndexed(array('username')), "username column should be indexed.");
    }

    /**
     * @group DDC-200
     */
    public function testPassColumnDefinitionToJoinColumn()
    {
        $customColumnDef = "MEDIUMINT(6) UNSIGNED NOT NULL";

        $em = $this->_getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $avatar = $em->getClassMetadata('Doctrine\Tests\Models\Forum\ForumAvatar');
        $avatar->fieldMappings['id']['columnDefinition'] = $customColumnDef;
        $user = $em->getClassMetadata('Doctrine\Tests\Models\Forum\ForumUser');

        $classes = array($avatar, $user);

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        $this->assertTrue($schema->hasTable('forum_users'));
        $table = $schema->getTable("forum_users");
        $this->assertTrue($table->hasColumn('avatar_id'));
        $this->assertEquals($customColumnDef, $table->getColumn('avatar_id')->getColumnDefinition());
    }

    /**
     * @group DDC-283
     */
    public function testPostGenerateEvents()
    {
        $listener = new GenerateSchemaEventListener();

        $em = $this->_getTestEntityManager();
        $em->getEventManager()->addEventListener(
            array(ToolEvents::postGenerateSchemaTable, ToolEvents::postGenerateSchema), $listener
        );
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

        $this->assertEquals(count($classes), $listener->tableCalls);
        $this->assertTrue($listener->schemaCalled);
    }
}

class GenerateSchemaEventListener
{
    public $tableCalls = 0;
    public $schemaCalled = false;

    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs)
    {
        $this->tableCalls++;
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs)
    {
        $this->schemaCalled = true;
    }
}