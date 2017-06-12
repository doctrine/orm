<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command\ClearCache;

use Doctrine\Common\Cache;
use Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Application;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @covers \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand<extended>
 */
class ResultCommandTest extends OrmFunctionalTestCase
{
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @var \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand
     */
    private $command;

    protected function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->application = new Application();
        $this->command     = new ResultCommand();

        $this->application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($this->_em)]));

        $this->application->add($this->command);
    }

    public function testFlush()
    {
        $this->_em->getConfiguration()->setResultCacheImpl(new Cache\ArrayCache());

        $command    = $this->application->find('orm:clear-cache:result');
        $tester     = new CommandTester($command);
        $tester->execute(
            [
                'command' => $command->getName(),
                '--flush'   => true,
            ], ['decorated' => false]
        );

        $expected = <<<'EOT'
Clearing ALL Result cache entries
Successfully flushed cache entries.

EOT;

        $this->assertEquals($expected, $tester->getDisplay());
    }

    /**
     * @param string $cacheClassName
     * @param string $cacheName
     *
     * @dataProvider dataProviderForTestFlushWithNoAccessToCacheException
     */
    public function testFlushWithNoAccessToCacheException($cacheClassName, $cacheName)
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot clear %s Cache from Console, its shared in the Webserver memory and not accessible from the CLI.',
            $cacheName
        ));

        /* @var $cache Cache\Cache|\PHPUnit_Framework_MockObject_MockObject */
        $cache = $this->createMock($cacheClassName);

        $this->_em->getConfiguration()->setResultCacheImpl($cache);

        $command = $this->application->find('orm:clear-cache:result');
        $tester = new CommandTester($command);
        $tester->execute(
            [
                'command' => $command->getName(),
                '--flush'   => true,
            ], ['decorated' => false]
        );
    }

    /**
     * @return array
     */
    public function dataProviderForTestFlushWithNoAccessToCacheException()
    {
        return [
            [
                Cache\ApcCache::class,
                'APC',
            ],
            [
                Cache\ApcuCache::class,
                'APCu',
            ],
            [
                Cache\XcacheCache::class,
                'XCache',
            ],
        ];
    }
}
