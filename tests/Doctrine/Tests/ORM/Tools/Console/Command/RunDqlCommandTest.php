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

        $this->application = new Application();
        $this->command     = new RunDqlCommand();

        $this->application->setHelperSet(new HelperSet(array(
            'em' => new EntityManagerHelper($this->_em)
        )));

        $this->application->add($this->command);

        $this->tester = new CommandTester($this->command);
    }

    public function testCommandName()
    {
        $this->assertSame($this->command, $this->application->get('orm:run-dql'));
    }

    public function testWillRunQuery()
    {
        $this->_em->persist(new DateTimeModel());
        $this->_em->flush();

        $this->assertSame(
            0,
            $this->tester->execute(array(
                'command' => $this->command->getName(),
                'dql'     => 'SELECT e FROM ' . DateTimeModel::CLASSNAME . ' e',
            ))
        );

        $this->assertContains(DateTimeModel::CLASSNAME, $this->tester->getDisplay());
    }

    public function testWillShowQuery()
    {
        $this->_em->persist(new DateTimeModel());
        $this->_em->flush();

        $this->assertSame(
            0,
            $this->tester->execute(array(
                'command'    => $this->command->getName(),
                'dql'        => 'SELECT e FROM ' . DateTimeModel::CLASSNAME . ' e',
                '--show-sql' => 'true'
            ))
        );

        $this->assertStringMatchesFormat('%Astring%sSELECT %a', $this->tester->getDisplay());
    }
}
