<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

class EntityRepositoryGeneratorTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var EntityGenerator
     */
    private $_generator;

    /**
     * @var EntityRepositoryGenerator
     */
    private $_repositoryGenerator;

    private $_tmpDir;
    private $_namespace;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->_namespace   = uniqid("doctrine_");
        $this->_tmpDir      = \sys_get_temp_dir();
        \mkdir($this->_tmpDir . \DIRECTORY_SEPARATOR . $this->_namespace);
        
        $this->_generator = new EntityGenerator();
        $this->_generator->setAnnotationPrefix("");
        $this->_generator->setGenerateAnnotations(true);
        $this->_generator->setGenerateStubMethods(true);
        $this->_generator->setRegenerateEntityIfExists(false);
        $this->_generator->setUpdateEntityIfExists(true);
        $this->_generator->setFieldVisibility(EntityGenerator::FIELD_VISIBLE_PROTECTED);
        
        $this->_repositoryGenerator = new EntityRepositoryGenerator();
    }

    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->_tmpDir . '/' . $this->_namespace));
        foreach ($ri AS $file) {
            /* @var $file \SplFileInfo */
            if ($file->isFile()) {
                \unlink($file->getPathname());
            }
        }
        rmdir($this->_tmpDir . '/' . $this->_namespace);
    }

    /**
     * @group DDC-1089
     */
    public function testGeneratedEntityRepositoryClass()
    {
        $em = $this->_getTestEntityManager();
        $ns = $this->_namespace;

        $className = 'DDC1089User';
        $this->writeEntityClass('Doctrine\Tests\Models\DDC1089\\' . $className, $ns . '\\' . $className);

        $rpath = $this->writeRepositoryClass($ns . '\\' . $className, 'Doctrine\Tests\Models\DDC1089\DDC1089EntityRepository');

        $this->assertNotNull($rpath);
        $this->assertFileExists($rpath);

        require $rpath;
        
        $repo = new \ReflectionClass($em->getRepository($ns . '\\' . $className));

        $this->assertSame($ns . '\\' . $className . 'Repository', $repo->getName());
        $this->assertSame('Doctrine\Tests\Models\DDC1089\DDC1089EntityRepository', $repo->getParentClass()->getName());


        $className2 = 'DDC1089User2';
        $this->writeEntityClass('Doctrine\Tests\Models\DDC1089\\' . $className2, $ns . '\\' . $className2);

        $rpath2 = $this->writeRepositoryClass($ns . '\\' . $className2);

        $this->assertNotNull($rpath2);
        $this->assertFileExists($rpath2);

        require $rpath2;

        $repo2 = new \ReflectionClass($em->getRepository($ns . '\\' . $className2));

        $this->assertSame($ns . '\\' . $className2 . 'Repository', $repo2->getName());
        $this->assertSame('Doctrine\ORM\EntityRepository', $repo2->getParentClass()->getName());
    }

    /**
     * @param string $className
     * @param string $newClassName
     */
    private function writeEntityClass($className, $newClassName)
    {
        $cmf    = new ClassMetadataFactory();
        $em     = $this->_getTestEntityManager();

        $cmf->setEntityManager($em);

        $metadata               = $cmf->getMetadataFor($className);
        $metadata->namespace    = $this->_namespace;
        $metadata->name         = $newClassName;
        $metadata->customRepositoryClassName = $newClassName . "Repository";

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        require $this->_tmpDir . '/' . str_replace('\\', '/', $newClassName) . ".php";
    }

    /**
     * @param string $className
     * @param string $defaultRepository
     * @return string
     */
    private function writeRepositoryClass($className, $defaultRepository = null)
    {
        $this->_repositoryGenerator->setDefaultRepositoryName($defaultRepository);
        
        return $this->_repositoryGenerator->writeEntityRepositoryClass($className . 'Repository', $this->_tmpDir);
    }
    
}
