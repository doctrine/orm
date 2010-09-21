<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\Driver\XmlDriver,
    Doctrine\ORM\Mapping\Driver\YamlDriver;

require_once __DIR__ . '/../../TestInit.php';

class YamlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        return new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');
    }

    /**
     * @group DDC-671
     *
     * Entities for this test are in AbstractMappingDriverTest
     */
    public function testJoinTablesWithMappedSuperclassForYamlDriver()
    {
        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($this->_loadDriver());

        $classPage = $em->getClassMetadata('Doctrine\Tests\ORM\Mapping\Page');
        $this->assertEquals('Doctrine\Tests\ORM\Mapping\Page', $classPage->associationMappings['parentDirectory']['sourceEntity']);
        $classDirectory = $em->getClassMetadata('Doctrine\Tests\ORM\Mapping\Directory');
        $this->assertEquals('Doctrine\Tests\ORM\Mapping\Directory', $classDirectory->associationMappings['parentDirectory']['sourceEntity']);

        $dql = "SELECT f FROM Doctrine\Tests\ORM\Mapping\Page f JOIN f.parentDirectory d " .
               "WHERE (d.url = :url) AND (f.extension = :extension)";

        $query = $em->createQuery($dql)
                    ->setParameter('url', "test")
                    ->setParameter('extension', "extension");

        $this->assertEquals(
            'SELECT c0_.id AS id0, c0_.extension AS extension1, c0_.parent_directory_id AS parent_directory_id2 FROM core_content_pages c0_ INNER JOIN core_content_directories c1_ ON c0_.parent_directory_id = c1_.id WHERE (c1_.url = ?) AND (c0_.extension = ?)',
            $query->getSql()
        );
    }

}
