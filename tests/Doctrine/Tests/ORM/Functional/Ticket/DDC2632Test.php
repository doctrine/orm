<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Export\Driver\YamlExporter;
use Doctrine\Tests\ORM\Functional\DatabaseDriverTestCase;
/**
 * @group DDC-2632
 */
class DDC2632Test extends  DatabaseDriverTestCase
{
    protected function _getType()
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }
        return 'yaml';
    }
    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $_sm = null;
    public function setUp()
    {
        //  $this->useModelSet('cms');
        parent::setUp();
        $this->_sm = $this->_em->getConnection()->getSchemaManager();
    }
    /**
     * @group DDC-2632
     */
    public function testFKDefaultValueOptionExport() {
        $exporter = new YamlExporter();

        if (!$this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }
        $user = new \Doctrine\DBAL\Schema\Table("ddc2059_user");
        $user->addColumn('id', 'integer', array('notnull' => true));
        $user->setPrimaryKey(array('id'));
        $project = new \Doctrine\DBAL\Schema\Table("ddc2059_project");
        $project->addColumn('id', 'integer', array('notnull' => false));
        $project->addColumn('user_id', 'integer', array('notnull' => true));
        $project->setPrimaryKey(array('id'),true);
        $project->addForeignKeyConstraint('ddc2059_user', array('user_id'), array('id'),array(),'fk');
        $this->_sm->dropAndCreateTable($user);
        $this->_sm->dropAndCreateTable($project);
        $metadata = $this->extractClassMetadata(array("Ddc2059User", "Ddc2059Project"));
        $expetedResult =  "joinColumns:"
            . "user_id:"
            . "referencedColumnName:id"
            . "nullable:false";
        $this->assertContains($expetedResult,$string = trim(preg_replace('/\s+/', '',preg_replace('/\t/', '', $exporter->exportClassMetadata($metadata['Ddc2059Project'])))));
    }
}