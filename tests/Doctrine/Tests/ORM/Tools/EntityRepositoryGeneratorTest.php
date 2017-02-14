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

class EntityRepositoryGeneratorTest extends OrmTestCase
{
    /**
     * @var EntityGenerator
     */
    private $generator;

    /**
     * @var EntityRepositoryGenerator
     */
    private $repositoryGenerator;

    private $tmpDir;
    private $namespace;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->namespace   = uniqid('doctrine_');
        $this->tmpDir      = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->namespace;
        \mkdir($this->tmpDir);

        $this->generator = new EntityGenerator();

        $this->generator->setGenerateAnnotations(true);
        $this->generator->setGenerateStubMethods(true);
        $this->generator->setRegenerateEntityIfExists(false);
        $this->generator->setUpdateEntityIfExists(true);
        $this->generator->setFieldVisibility(EntityGenerator::FIELD_VISIBLE_PROTECTED);

        $this->repositoryGenerator = new EntityRepositoryGenerator();
    }

    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        $dirs = [];

        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->tmpDir));
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

    /**
     * @group DDC-3231
     */
    public function testGeneratedEntityRepositoryClass()
    {
        $em = $this->getTestEntityManager();
        $ns = $this->namespace;

        $className = $ns . '\DDC3231User1Tmp';
        $this->writeEntityClass(DDC3231User1::class, $className);

        $rpath = $this->writeRepositoryClass($className);

        self::assertFileExists($rpath);

        require $rpath;

        $repo = new \ReflectionClass($em->getRepository($className));

        self::assertTrue($repo->inNamespace());
        self::assertSame($className . 'Repository', $repo->getName());
        self::assertSame(EntityRepository::class, $repo->getParentClass()->getName());

        require_once __DIR__ . '/../../Models/DDC3231/DDC3231User1NoNamespace.php';

        $className2 = 'DDC3231User1NoNamespaceTmp';
        $this->writeEntityClass(\DDC3231User1NoNamespace::class, $className2);

        $rpath2 = $this->writeRepositoryClass($className2);

        self::assertFileExists($rpath2);

        require $rpath2;

        $repo2 = new \ReflectionClass($em->getRepository($className2));

        self::assertFalse($repo2->inNamespace());
        self::assertSame($className2 . 'Repository', $repo2->getName());
        self::assertSame(EntityRepository::class, $repo2->getParentClass()->getName());
    }

    /**
     * @group DDC-3231
     */
    public function testGeneratedEntityRepositoryClassCustomDefaultRepository()
    {
        $em = $this->getTestEntityManager();
        $ns = $this->namespace;

        $className = $ns . '\DDC3231User2Tmp';
        $this->writeEntityClass(DDC3231User2::class, $className);

        $rpath = $this->writeRepositoryClass($className, DDC3231EntityRepository::class);

        self::assertNotNull($rpath);
        self::assertFileExists($rpath);

        require $rpath;

        $repo = new \ReflectionClass($em->getRepository($className));

        self::assertTrue($repo->inNamespace());
        self::assertSame($className . 'Repository', $repo->getName());
        self::assertSame(DDC3231EntityRepository::class, $repo->getParentClass()->getName());


        require_once __DIR__ . '/../../Models/DDC3231/DDC3231User2NoNamespace.php';

        $className2 = 'DDC3231User2NoNamespaceTmp';
        $this->writeEntityClass('DDC3231User2NoNamespace', $className2);

        $rpath2 = $this->writeRepositoryClass($className2, DDC3231EntityRepository::class);

        self::assertNotNull($rpath2);
        self::assertFileExists($rpath2);

        require $rpath2;

        $repo2 = new \ReflectionClass($em->getRepository($className2));

        self::assertFalse($repo2->inNamespace());
        self::assertSame($className2 . 'Repository', $repo2->getName());
        self::assertSame(DDC3231EntityRepository::class, $repo2->getParentClass()->getName());
    }

    /**
     * @param string $className
     * @param string $newClassName
     */
    private function writeEntityClass($className, $newClassName)
    {
        $cmf    = new ClassMetadataFactory();
        $em     = $this->getTestEntityManager();

        $cmf->setEntityManager($em);

        $metadata               = $cmf->getMetadataFor($className);
        $metadata->namespace    = $this->namespace;
        $metadata->name         = $newClassName;
        $metadata->customRepositoryClassName = $newClassName . "Repository";

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        require $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $newClassName) . ".php";
    }

    /**
     * @param string $className
     * @param string $defaultRepository
     * @return string
     */
    private function writeRepositoryClass($className, $defaultRepository = null)
    {
        $this->repositoryGenerator->setDefaultRepositoryName($defaultRepository);

        $this->repositoryGenerator->writeEntityRepositoryClass($className . 'Repository', $this->tmpDir);

        return $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . 'Repository.php';
    }

}
