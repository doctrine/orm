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

    public function testJoinTablesWithMappedSuperclassForYamlDriver()
    {
        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\YamlDriver(__DIR__ . '/yaml/'));
        $qb = $em->createQueryBuilder();

        $qb->select('f')
                ->from('Doctrine\Tests\ORM\Mapping\Page', 'f')
                ->join('f.parentDirectory', 'd')
                ->where(
                        $qb->expr()->andx(
                                $qb->expr()->eq('d.url', ':url'),
                                $qb->expr()->eq('f.extension', ':extension')
                        )
                )
                ->setParameter('url', "test")
                ->setParameter('filename', "filename")
                ->setParameter('extension', "extension");

        // Is there a way to generalize this more? (Instead of a2_., check if the table prefix in the ON clause is set within a FROM or JOIN clause..)
        $this->assertTrue(strpos($qb->getQuery()->getSql(), 'a2_.') === false);
    }

}

class Directory extends AbstractContentItem
{

    protected $subDirectories;
    /**
     * This is a collection of files that are contained in this Directory. Files, for example, could be Pages, but even other files
     * like media files (css, images etc) are possible.
     * 
     * @var \Doctrine\Common\Collections\Collection
     * @access protected
     */
    protected $containedFiles;
    /**
     * @var string
     */
    protected $url;
}

class Page extends AbstractContentItem
{

    protected $extension = "html";

}

abstract class AbstractContentItem
{

    /**
     * Doctrine2 entity id
     * @var integer
     */
    private $id;
    /**
     * The parent directory
     * @var Directory
     */
    protected $parentDirectory;
    /**
     * Filename (without extension) or directory name
     * @var string
     */
    protected $name;
}
