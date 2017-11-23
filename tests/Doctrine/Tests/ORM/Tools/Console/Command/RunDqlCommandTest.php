<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\RunDqlCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for {@see \Doctrine\ORM\Tools\Console\Command\RunDqlCommand}
 *
 * @covers \Doctrine\ORM\Tools\Console\Command\RunDqlCommand
 */
class RunDqlCommandTest extends OrmFunctionalTestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var RunDqlCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $tester;

    protected function setUp()
    {
        $this->useModelSet('generic');

        parent::setUp();

        $this->command = new RunDqlCommand();

        $this->application = new Application();
        $this->application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($this->_em)]));
        $this->application->add($this->command);

        $this->tester = new CommandTester($this->command);
    }

    public function testCommandName()
    {
        self::assertSame($this->command, $this->application->get('orm:run-dql'));
    }

    public function testWillRunQuery()
    {
        $this->_em->persist(new DateTimeModel());
        $this->_em->flush();

        self::assertSame(
            0,
            $this->tester->execute(
                [
                    'command' => $this->command->getName(),
                    'dql'     => 'SELECT e FROM ' . DateTimeModel::class . ' e',
                ]
            )
        );

        self::assertContains(DateTimeModel::class, $this->tester->getDisplay());
    }

    public function testWillShowQuery()
    {
        $this->_em->persist(new DateTimeModel());
        $this->_em->flush();

        self::assertSame(
            0,
            $this->tester->execute(
                [
                    'command'    => $this->command->getName(),
                    'dql'        => 'SELECT e FROM ' . DateTimeModel::class . ' e',
                    '--show-sql' => 'true',
                ]
            )
        );

        self::assertStringMatchesFormat('SELECT %a', trim($this->tester->getDisplay()));
    }
}
