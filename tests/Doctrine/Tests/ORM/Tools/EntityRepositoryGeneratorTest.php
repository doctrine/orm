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
    /** @var EntityGenerator */
    private $generator;

    /** @var EntityRepositoryGenerator */
    private $repositoryGenerator;

    /** @var string */
    private $tmpDir;

    /** @var string */
    private $namespace;

    protected function setUp(): void
    {
        $this->namespace = uniqid('doctrine_');
        $this->tmpDir    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->namespace;
        mkdir($this->tmpDir);

        $this->generator = new EntityGenerator();
        $this->generator->setGenerateAnnotations(true);
        $this->generator->setGenerateStubMethods(true);
        $this->generator->setRegenerateEntityIfExists(false);
        $this->generator->setUpdateEntityIfExists(true);
        $this->generator->setFieldVisibility(EntityGenerator::FIELD_VISIBLE_PROTECTED);

        $this->repositoryGenerator = new EntityRepositoryGenerator();
    }

    public function tearDown(): void
    {
        $dirs = [];

        $ri = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->tmpDir));
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

    /** @group DDC-3231 */
    public function testGeneratedEntityRepositoryClass(): void
    {
        $em = $this->getTestEntityManager();
        $ns = $this->namespace;

        $className = $ns . '\DDC3231User1Tmp';
        $this->writeEntityClass(DDC3231User1::class, $className);

        $rpath = $this->writeRepositoryClass($className);

        self::assertFileExists($rpath);

        require $rpath;

        $repo = new ReflectionClass($em->getRepository($className));

        self::assertTrue($repo->inNamespace());
        self::assertSame($className . 'Repository', $repo->getName());
        self::assertSame(EntityRepository::class, $repo->getParentClass()->getName());

        require_once __DIR__ . '/../../Models/DDC3231/DDC3231User1NoNamespace.php';

        $className2 = 'DDC3231User1NoNamespaceTmp';
        $this->writeEntityClass(DDC3231User1NoNamespace::class, $className2);

        $rpath2 = $this->writeRepositoryClass($className2);

        self::assertFileExists($rpath2);

        require $rpath2;

        $repo2 = new ReflectionClass($em->getRepository($className2));

        self::assertFalse($repo2->inNamespace());
        self::assertSame($className2 . 'Repository', $repo2->getName());
        self::assertSame(EntityRepository::class, $repo2->getParentClass()->getName());
    }

    /** @group DDC-3231 */
    public function testGeneratedEntityRepositoryClassCustomDefaultRepository(): void
    {
        $em = $this->getTestEntityManager();
        $ns = $this->namespace;

        $className = $ns . '\DDC3231User2Tmp';
        $this->writeEntityClass(DDC3231User2::class, $className);

        $rpath = $this->writeRepositoryClass($className, DDC3231EntityRepository::class);

        self::assertNotNull($rpath);
        self::assertFileExists($rpath);

        require $rpath;

        $repo = new ReflectionClass($em->getRepository($className));

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

        $repo2 = new ReflectionClass($em->getRepository($className2));

        self::assertFalse($repo2->inNamespace());
        self::assertSame($className2 . 'Repository', $repo2->getName());
        self::assertSame(DDC3231EntityRepository::class, $repo2->getParentClass()->getName());
    }

    private function writeEntityClass(string $className, string $newClassName): void
    {
        $cmf = new ClassMetadataFactory();
        $em  = $this->getTestEntityManager();

        $cmf->setEntityManager($em);

        $metadata                            = $cmf->getMetadataFor($className);
        $metadata->namespace                 = $this->namespace;
        $metadata->name                      = $newClassName;
        $metadata->customRepositoryClassName = $newClassName . 'Repository';

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        require $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $newClassName) . '.php';
    }

    private function writeRepositoryClass(string $className, ?string $defaultRepository = null): string
    {
        $this->repositoryGenerator->setDefaultRepositoryName($defaultRepository);

        $this->repositoryGenerator->writeEntityRepositoryClass($className . 'Repository', $this->tmpDir);

        return $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . 'Repository.php';
    }
}
