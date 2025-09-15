<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\Debug;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Console\ApplicationCompatibility;
use Doctrine\ORM\Tools\Console\Command\Debug\DebugEntityListenersDoctrineCommand;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BarListener;
use Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BazListener;
use Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\FooListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DebugEntityListenersDoctrineCommandTest extends TestCase
{
    use ApplicationCompatibility;

    private DebugEntityListenersDoctrineCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $application   = new Application();
        $this->command = new DebugEntityListenersDoctrineCommand($this->getMockManagerRegistry());

        self::addCommandToApplication($application, $this->command);
    }

    public function testExecute(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            ['command' => $this->command->getName(), 'entity' => self::class],
        );

        self::assertSame(<<<'TXT'

Entity listeners for Doctrine\Tests\ORM\Tools\Console\Command\Debug\DebugEntityListenersDoctrineCommandTest
===========================================================================================================

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
            ['command' => $this->command->getName(), 'entity' => self::class, 'event' => 'postPersists'],
        );

        self::assertSame(<<<'TXT'

Entity listeners for Doctrine\Tests\ORM\Tools\Console\Command\Debug\DebugEntityListenersDoctrineCommandTest
===========================================================================================================

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
            ['command' => $this->command->getName(), 'entity' => self::class, 'event' => 'preRemove'],
        );

        self::assertSame(<<<'TXT'

Entity listeners for Doctrine\Tests\ORM\Tools\Console\Command\Debug\DebugEntityListenersDoctrineCommandTest
===========================================================================================================

"preRemove" event
-----------------

 No listeners are configured for this event.

TXT
            , $commandTester->getDisplay(true));
    }

    /** @return MockObject&ManagerRegistry */
    private function getMockManagerRegistry(): MockObject
    {
        $mappingDriverMock = $this->createMock(MappingDriver::class);
        $mappingDriverMock->method('getAllClassNames')->willReturn([self::class]);

        $config = new Configuration();
        $config->setMetadataDriverImpl($mappingDriverMock);

        $classMetadata = new ClassMetadata(self::class);
        $classMetadata->addEntityListener('preUpdate', FooListener::class, 'preUpdate');
        $classMetadata->addEntityListener('preUpdate', BarListener::class, '__invoke');
        $classMetadata->addEntityListener('postPersists', BazListener::class, 'postPersists');

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getConfiguration')->willReturn($config);
        $emMock->method('getClassMetadata')->willReturn($classMetadata);

        $doctrineMock = $this->createMock(ManagerRegistry::class);
        $doctrineMock->method('getManagerNames')->willReturn(['default']);
        $doctrineMock->method('getManager')->willReturn($emMock);
        $doctrineMock->method('getManagerForClass')->willReturn($emMock);

        return $doctrineMock;
    }
}
