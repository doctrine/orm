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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Completion\Suggestion;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;

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

"postPersist" event
-------------------

 ------- ------------------------------------------------------------------------------------ 
  Order   Listener                                                                            
 ------- ------------------------------------------------------------------------------------ 
  #1      Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BazListener::postPersist()  
 ------- ------------------------------------------------------------------------------------ 

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
            ['command' => $this->command->getName(), 'entity' => self::class, 'event' => 'postPersist'],
        );

        self::assertSame(<<<'TXT'

Entity listeners for Doctrine\Tests\ORM\Tools\Console\Command\Debug\DebugEntityListenersDoctrineCommandTest
===========================================================================================================

"postPersist" event
-------------------

 ------- ------------------------------------------------------------------------------------ 
  Order   Listener                                                                            
 ------- ------------------------------------------------------------------------------------ 
  #1      Doctrine\Tests\ORM\Tools\Console\Command\Debug\Fixtures\BazListener::postPersist()  
 ------- ------------------------------------------------------------------------------------ 


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

    /**
     * @param list<string> $args
     * @param list<string> $expectedSuggestions
     */
    #[TestWith([['console'], 1, [self::class]])]
    #[TestWith([['console', self::class], 2, ['preUpdate', 'postPersist']])]
    #[TestWith([['console', 'NonExistentEntity'], 2, []])]
    public function testComplete(array $args, int $currentIndex, array $expectedSuggestions): void
    {
        $input = CompletionInput::fromTokens($args, $currentIndex);
        $input->bind($this->command->getDefinition());
        $suggestions = new CompletionSuggestions();

        $this->command->complete($input, $suggestions);

        self::assertSame($expectedSuggestions, array_map(static fn (Suggestion $suggestion) => $suggestion->getValue(), $suggestions->getValueSuggestions()));
    }

    /** @return MockObject&ManagerRegistry */
    private function getMockManagerRegistry(): ManagerRegistry
    {
        $mappingDriverMock = $this->createMock(MappingDriver::class);
        $mappingDriverMock->method('getAllClassNames')->willReturn([self::class]);

        $config = new Configuration();
        $config->setMetadataDriverImpl($mappingDriverMock);

        $classMetadata = new ClassMetadata(self::class);
        $classMetadata->addEntityListener('preUpdate', FooListener::class, 'preUpdate');
        $classMetadata->addEntityListener('preUpdate', BarListener::class, '__invoke');
        $classMetadata->addEntityListener('postPersist', BazListener::class, 'postPersist');

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getConfiguration')->willReturn($config);
        $emMock->method('getClassMetadata')->willReturn($classMetadata);

        $doctrineMock = $this->createMock(ManagerRegistry::class);
        $doctrineMock->method('getManagerNames')->willReturn(['default' => 'entity_manager.default']);
        $doctrineMock->method('getManager')->willReturn($emMock);
        $doctrineMock->method('getManagerForClass')->willReturn($emMock);

        return $doctrineMock;
    }
}
