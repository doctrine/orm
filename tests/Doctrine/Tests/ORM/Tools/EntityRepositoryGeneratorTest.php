<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Doctrine\Tests\Models\DDC3231\DDC3231EntityRepository;
use Doctrine\Tests\Models\DDC3231\DDC3231User1;
use Doctrine\Tests\Models\DDC3231\DDC3231User2;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\VerifyDeprecations;

class EntityRepositoryGeneratorTest extends OrmTestCase
{
    use VerifyDeprecations;

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
        $this->_namespace   = uniqid('doctrine_');
        $this->_tmpDir      = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->_namespace;
        \mkdir($this->_tmpDir);

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
        $dirs = [];

        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->_tmpDir));
        foreach ($ri AS $file) {
            /* @var $file \SplFileInfo */
            if ($file->isFile()) {
                \unlink($file->getPathname());
            } elseif ($file->getBasename() === '.') {
                $dirs[] = $file->getRealPath();
            }
        }

        arsort($dirs);

        foreach ($dirs as $dir) {
            \rmdir($dir);
        }
    }

    /** @after */
    public function ensureTestGeneratedDeprecationMessages() : void
    {
        $this->assertHasDeprecationMessages();
    }

    /**
     * @group DDC-3231
     */
    public function testGeneratedEntityRepositoryClass()
    {
        $em = $this->_getTestEntityManager();
        $ns = $this->_namespace;

        $className = $ns . '\DDC3231User1Tmp';
        $this->writeEntityClass(DDC3231User1::class, $className);

        $rpath = $this->writeRepositoryClass($className);

        $this->assertFileExists($rpath);

        require $rpath;

        $repo = new \ReflectionClass($em->getRepository($className));

        $this->assertTrue($repo->inNamespace());
        $this->assertSame($className . 'Repository', $repo->getName());
        $this->assertSame(EntityRepository::class, $repo->getParentClass()->getName());

        require_once __DIR__ . '/../../Models/DDC3231/DDC3231User1NoNamespace.php';

        $className2 = 'DDC3231User1NoNamespaceTmp';
        $this->writeEntityClass(\DDC3231User1NoNamespace::class, $className2);

        $rpath2 = $this->writeRepositoryClass($className2);

        $this->assertFileExists($rpath2);

        require $rpath2;

        $repo2 = new \ReflectionClass($em->getRepository($className2));

        $this->assertFalse($repo2->inNamespace());
        $this->assertSame($className2 . 'Repository', $repo2->getName());
        $this->assertSame(EntityRepository::class, $repo2->getParentClass()->getName());
    }

    /**
     * @group DDC-3231
     */
    public function testGeneratedEntityRepositoryClassCustomDefaultRepository()
    {
        $em = $this->_getTestEntityManager();
        $ns = $this->_namespace;

        $className = $ns . '\DDC3231User2Tmp';
        $this->writeEntityClass(DDC3231User2::class, $className);

        $rpath = $this->writeRepositoryClass($className, DDC3231EntityRepository::class);

        $this->assertNotNull($rpath);
        $this->assertFileExists($rpath);

        require $rpath;

        $repo = new \ReflectionClass($em->getRepository($className));

        $this->assertTrue($repo->inNamespace());
        $this->assertSame($className . 'Repository', $repo->getName());
        $this->assertSame(DDC3231EntityRepository::class, $repo->getParentClass()->getName());


        require_once __DIR__ . '/../../Models/DDC3231/DDC3231User2NoNamespace.php';

        $className2 = 'DDC3231User2NoNamespaceTmp';
        $this->writeEntityClass('DDC3231User2NoNamespace', $className2);

        $rpath2 = $this->writeRepositoryClass($className2, DDC3231EntityRepository::class);

        $this->assertNotNull($rpath2);
        $this->assertFileExists($rpath2);

        require $rpath2;

        $repo2 = new \ReflectionClass($em->getRepository($className2));

        $this->assertFalse($repo2->inNamespace());
        $this->assertSame($className2 . 'Repository', $repo2->getName());
        $this->assertSame(DDC3231EntityRepository::class, $repo2->getParentClass()->getName());
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

        require $this->_tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $newClassName) . ".php";
    }

    /**
     * @param string $className
     * @param string $defaultRepository
     * @return string
     */
    private function writeRepositoryClass($className, $defaultRepository = null)
    {
        $this->_repositoryGenerator->setDefaultRepositoryName($defaultRepository);

        $this->_repositoryGenerator->writeEntityRepositoryClass($className . 'Repository', $this->_tmpDir);

        return $this->_tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . 'Repository.php';
    }

}
