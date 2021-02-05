<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use DDC3231User1NoNamespace;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Doctrine\Tests\Models\DDC3231\DDC3231EntityRepository;
use Doctrine\Tests\Models\DDC3231\DDC3231User1;
use Doctrine\Tests\Models\DDC3231\DDC3231User2;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\VerifyDeprecations;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

use function arsort;
use function assert;
use function mkdir;
use function rmdir;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

class EntityRepositoryGeneratorTest extends OrmTestCase
{
    use VerifyDeprecations;

    /** @var EntityGenerator */
    private $_generator;

    /** @var EntityRepositoryGenerator */
    private $_repositoryGenerator;

    private $_tmpDir;
    private $_namespace;

    protected function setUp(): void
    {
        $this->_namespace = uniqid('doctrine_');
        $this->_tmpDir    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->_namespace;
        mkdir($this->_tmpDir);

        $this->_generator = new EntityGenerator();
        $this->_generator->setAnnotationPrefix('');
        $this->_generator->setGenerateAnnotations(true);
        $this->_generator->setGenerateStubMethods(true);
        $this->_generator->setRegenerateEntityIfExists(false);
        $this->_generator->setUpdateEntityIfExists(true);
        $this->_generator->setFieldVisibility(EntityGenerator::FIELD_VISIBLE_PROTECTED);

        $this->_repositoryGenerator = new EntityRepositoryGenerator();
    }

    public function tearDown(): void
    {
        $dirs = [];

        $ri = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_tmpDir));
        foreach ($ri as $file) {
            assert($file instanceof SplFileInfo);
            if ($file->isFile()) {
                unlink($file->getPathname());
            } elseif ($file->getBasename() === '.') {
                $dirs[] = $file->getRealPath();
            }
        }

        arsort($dirs);

        foreach ($dirs as $dir) {
            rmdir($dir);
        }
    }

    /** @after */
    public function ensureTestGeneratedDeprecationMessages(): void
    {
        $this->assertHasDeprecationMessages();
    }

    /**
     * @group DDC-3231
     */
    public function testGeneratedEntityRepositoryClass(): void
    {
        $em = $this->_getTestEntityManager();
        $ns = $this->_namespace;

        $className = $ns . '\DDC3231User1Tmp';
        $this->writeEntityClass(DDC3231User1::class, $className);

        $rpath = $this->writeRepositoryClass($className);

        $this->assertFileExists($rpath);

        require $rpath;

        $repo = new ReflectionClass($em->getRepository($className));

        $this->assertTrue($repo->inNamespace());
        $this->assertSame($className . 'Repository', $repo->getName());
        $this->assertSame(EntityRepository::class, $repo->getParentClass()->getName());

        require_once __DIR__ . '/../../Models/DDC3231/DDC3231User1NoNamespace.php';

        $className2 = 'DDC3231User1NoNamespaceTmp';
        $this->writeEntityClass(DDC3231User1NoNamespace::class, $className2);

        $rpath2 = $this->writeRepositoryClass($className2);

        $this->assertFileExists($rpath2);

        require $rpath2;

        $repo2 = new ReflectionClass($em->getRepository($className2));

        $this->assertFalse($repo2->inNamespace());
        $this->assertSame($className2 . 'Repository', $repo2->getName());
        $this->assertSame(EntityRepository::class, $repo2->getParentClass()->getName());
    }

    /**
     * @group DDC-3231
     */
    public function testGeneratedEntityRepositoryClassCustomDefaultRepository(): void
    {
        $em = $this->_getTestEntityManager();
        $ns = $this->_namespace;

        $className = $ns . '\DDC3231User2Tmp';
        $this->writeEntityClass(DDC3231User2::class, $className);

        $rpath = $this->writeRepositoryClass($className, DDC3231EntityRepository::class);

        $this->assertNotNull($rpath);
        $this->assertFileExists($rpath);

        require $rpath;

        $repo = new ReflectionClass($em->getRepository($className));

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

        $repo2 = new ReflectionClass($em->getRepository($className2));

        $this->assertFalse($repo2->inNamespace());
        $this->assertSame($className2 . 'Repository', $repo2->getName());
        $this->assertSame(DDC3231EntityRepository::class, $repo2->getParentClass()->getName());
    }

    private function writeEntityClass(string $className, string $newClassName): void
    {
        $cmf = new ClassMetadataFactory();
        $em  = $this->_getTestEntityManager();

        $cmf->setEntityManager($em);

        $metadata                            = $cmf->getMetadataFor($className);
        $metadata->namespace                 = $this->_namespace;
        $metadata->name                      = $newClassName;
        $metadata->customRepositoryClassName = $newClassName . 'Repository';

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        require $this->_tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $newClassName) . '.php';
    }

    private function writeRepositoryClass(string $className, ?string $defaultRepository = null): string
    {
        $this->_repositoryGenerator->setDefaultRepositoryName($defaultRepository);

        $this->_repositoryGenerator->writeEntityRepositoryClass($className . 'Repository', $this->_tmpDir);

        return $this->_tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . 'Repository.php';
    }
}
