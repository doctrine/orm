<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\Debug;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ApplicationCompatibility;
use Doctrine\ORM\Tools\Console\Command\Debug\DebugEventManagerDoctrineCommand;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BarListener;
use Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BazListener;
use Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\FooListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DebugEventManagerDoctrineCommandTest extends TestCase
{
    use ApplicationCompatibility;

    private DebugEventManagerDoctrineCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $application   = new Application();
        $this->command = new DebugEventManagerDoctrineCommand($this->getMockManagerRegistry());

        self::addCommandToApplication($application, $this->command);
    }

    public function testExecute(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            ['command' => $this->command->getName()],
        );

        self::assertSame(<<<'TXT'

Event listeners for default entity manager
==========================================

"postPersists" event
--------------------

 ------- ------------------------------------------------------------------------------------- 
  Order   Listener                                                                             
 ------- ------------------------------------------------------------------------------------- 
  #1      Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BazListener::postPersists()  
 ------- ------------------------------------------------------------------------------------- 

"preUpdate" event
-----------------

 ------- ---------------------------------------------------------------------------------- 
  Order   Listener                                                                          
 ------- ---------------------------------------------------------------------------------- 
  #1      Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\FooListener::preUpdate()  
  #2      Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BarListener::__invoke()   
 ------- ---------------------------------------------------------------------------------- 


TXT
            , $commandTester->getDisplay(true));
    }

    public function testExecuteWithEvent(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            ['command' => $this->command->getName(), 'event' => 'postPersists'],
        );

        self::assertSame(<<<'TXT'

Event listeners for default entity manager
==========================================

"postPersists" event
--------------------

 ------- ------------------------------------------------------------------------------------- 
  Order   Listener                                                                             
 ------- ------------------------------------------------------------------------------------- 
  #1      Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BazListener::postPersists()  
 ------- ------------------------------------------------------------------------------------- 


TXT
            , $commandTester->getDisplay(true));
    }

    public function testExecuteWithMissingEvent(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            ['command' => $this->command->getName(), 'event' => 'preRemove'],
        );

        self::assertSame(<<<'TXT'

Event listeners for default entity manager
==========================================

"preRemove" event
-----------------

 No listeners are configured for this event.

TXT
            , $commandTester->getDisplay(true));
    }

    /** @return MockObject&ManagerRegistry */
    private function getMockManagerRegistry(): MockObject
    {
        $eventManager = new EventManager();
        $eventManager->addEventListener('preUpdate', new FooListener());
        $eventManager->addEventListener('preUpdate', new BarListener());
        $eventManager->addEventListener('postPersists', new BazListener());

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getEventManager')->willReturn($eventManager);

        $doctrineMock = $this->createMock(ManagerRegistry::class);
        $doctrineMock->method('getDefaultManagerName')->willReturn('default');
        $doctrineMock->method('getManager')->willReturn($emMock);

        return $doctrineMock;
    }
}
