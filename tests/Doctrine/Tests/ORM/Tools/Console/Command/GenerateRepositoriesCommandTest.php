<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\Tests\Models\DDC3231\DDC3231EntityRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Application;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * GenerateRepositoriesCommandTest
 */
class GenerateRepositoriesCommandTest extends OrmFunctionalTestCase
{
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    private $path;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->path = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('doctrine_');

        \mkdir($this->path);


        $metadataDriver = $this->em->getConfiguration()->getMetadataDriverImpl();

        $metadataDriver->addPaths(
            [
            __DIR__ . '/../../../../Models/DDC3231/'
            ]
        );

        $this->application = new Application();

        $this->application->setHelperSet(new HelperSet(
            [
            'em' => new EntityManagerHelper($this->em)
            ]
        ));

        $this->application->add(new GenerateRepositoriesCommand());

    }

    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        $dirs = [];

        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path));
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

        parent::tearDown();
    }

    public function testGenerateRepositories()
    {
        $this->generateRepositories('DDC3231User1');

        $cname = 'Doctrine\Tests\Models\DDC3231\DDC3231User1Repository';
        $fname = str_replace('\\', DIRECTORY_SEPARATOR, $cname) . '.php';

        self::assertFileExists($this->path . DIRECTORY_SEPARATOR . $fname);
        self::assertFileExists($this->path . DIRECTORY_SEPARATOR . 'DDC3231User1NoNamespaceRepository.php');

        require $this->path . DIRECTORY_SEPARATOR . $fname;
        require $this->path . DIRECTORY_SEPARATOR . 'DDC3231User1NoNamespaceRepository.php';

        self::assertTrue(class_exists($cname));
        self::assertTrue(class_exists('DDC3231User1NoNamespaceRepository'));

        $repo1  = new \ReflectionClass($cname);
        $repo2  = new \ReflectionClass('DDC3231User1NoNamespaceRepository');

        self::assertSame(EntityRepository::class, $repo1->getParentClass()->getName());
        self::assertSame(EntityRepository::class, $repo2->getParentClass()->getName());
    }

    public function testGenerateRepositoriesCustomDefaultRepository()
    {
        $this->generateRepositories('DDC3231User2', DDC3231EntityRepository::class);

        $cname = 'Doctrine\Tests\Models\DDC3231\DDC3231User2Repository';
        $fname = str_replace('\\', DIRECTORY_SEPARATOR, $cname) . '.php';

        self::assertFileExists($this->path . DIRECTORY_SEPARATOR . $fname);
        self::assertFileExists($this->path . DIRECTORY_SEPARATOR . 'DDC3231User2NoNamespaceRepository.php');

        require $this->path . DIRECTORY_SEPARATOR . $fname;
        require $this->path . DIRECTORY_SEPARATOR . 'DDC3231User2NoNamespaceRepository.php';

        self::assertTrue(class_exists($cname));
        self::assertTrue(class_exists('DDC3231User2NoNamespaceRepository'));

        $repo1  = new \ReflectionClass($cname);
        $repo2  = new \ReflectionClass('DDC3231User2NoNamespaceRepository');

        self::assertSame(DDC3231EntityRepository::class, $repo1->getParentClass()->getName());
        self::assertSame(DDC3231EntityRepository::class, $repo2->getParentClass()->getName());
    }

    /**
     * @param string $filter
     * @param string $defaultRepository
     */
    private function generateRepositories($filter, $defaultRepository = null)
    {
        if ($defaultRepository) {
            $this->em->getConfiguration()->setDefaultRepositoryClassName($defaultRepository);
        }

        $command    = $this->application->find('orm:generate-repositories');
        $tester     = new CommandTester($command);
        $tester->execute(
            [
            'command'   => $command->getName(),
            'dest-path' => $this->path,
            '--filter'  => $filter,
            ]
        );
    }

}
