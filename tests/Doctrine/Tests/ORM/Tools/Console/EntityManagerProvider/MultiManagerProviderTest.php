<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\MultiManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\UnknownManagerException;
use Doctrine\Tests\Models\Taxi\Car;
use Doctrine\Tests\Models\Taxi\Driver;
use Doctrine\Tests\Models\Taxi\PaidRide;
use Doctrine\Tests\Models\Taxi\Ride;
use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User as Tweep;
use Doctrine\Tests\Models\Tweet\UserList;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test the MultiManagerProvider tool
 *
 * @covers \Doctrine\ORM\Tools\Console\EntityManagerProvider\MultiManagerProvider
 */
class MultiManagerProviderTest extends OrmFunctionalTestCase
{

    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManagerTweet = $this->getEntityManager(null, ORMSetup::createDefaultAnnotationDriver([__DIR__ . '../../../../../Models/Tweet']));

        $entityManagerTaxi = $this->getEntityManager(null, ORMSetup::createDefaultAnnotationDriver([__DIR__ . '../../../../../Models/Taxi']));

        $multiManagerProvider = new MultiManagerProvider([
            'tweet' => $entityManagerTweet,
            'taxi' => $entityManagerTaxi,
        ], 'tweet');

        $application = ConsoleRunner::createApplication($multiManagerProvider);
        $this->command     = $application->find('orm:info');
        $this->tester      = new CommandTester($this->command);
    }

    public function testGetDefaultManager(): void
    {
        $result = $this->tester->execute(['command' => $this->command->getName()]);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString(Tweet::class, $this->tester->getDisplay());
        self::assertStringContainsString(Tweep::class, $this->tester->getDisplay());
        self::assertStringContainsString(UserList::class, $this->tester->getDisplay());
    }

    public function testGetDifferentManager(): void
    {
        $result = $this->tester->execute(['command' => $this->command->getName(), '--em' => 'taxi']);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString(Car::class, $this->tester->getDisplay());
        self::assertStringContainsString(Driver::class, $this->tester->getDisplay());
        self::assertStringContainsString(PaidRide::class, $this->tester->getDisplay());
        self::assertStringContainsString(Ride::class, $this->tester->getDisplay());
    }

    public function testGetInvalidManager(): void
    {
        self::expectException(UnknownManagerException::class);

        $result = $this->tester->execute(['command' => $this->command->getName(), '--em' => 'invalid-manager']);

        self::assertSame(Command::FAILURE, $result);
    }
}
