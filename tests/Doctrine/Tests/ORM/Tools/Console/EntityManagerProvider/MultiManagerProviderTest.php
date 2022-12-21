<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\MultiManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\UnknownManagerException;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Test the MultiManagerProvider tool
 *
 * @covers \Doctrine\ORM\Tools\Console\EntityManagerProvider\MultiManagerProvider
 */
class MultiManagerProviderTest extends OrmFunctionalTestCase
{
    /** @var MultiManagerProvider */
    private $multiManagerProvider;

    /** @var EntityManagerInterface[] */
    private $entityManagers;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $entityManagerTweet = $this->getEntityManager(null, ORMSetup::createDefaultAnnotationDriver([
            __DIR__ . '/Models/Tweet',
        ]));

        $entityManagerTaxi = $this->getEntityManager(null, ORMSetup::createDefaultAnnotationDriver([
            __DIR__ . '/Models/Taxi',
        ]));

        $this->entityManagers = [
            'tweet' => $entityManagerTweet,
            'taxi' => $entityManagerTaxi,
        ];

        $this->multiManagerProvider = new MultiManagerProvider([
            'tweet' => $entityManagerTweet,
            'taxi' => $entityManagerTaxi,
        ], 'tweet');
    }

    public function testGetDefaultManager(): void
    {
        self::assertSame($this->entityManagers['tweet'], $this->multiManagerProvider->getDefaultManager());
    }

    public function testGetManager(): void
    {
        self::assertSame($this->entityManagers['taxi'], $this->multiManagerProvider->getManager('taxi'));
    }

    public function testUnknownManager(): void
    {
        self::expectException(UnknownManagerException::class);

        $this->multiManagerProvider->getManager('notfound');
    }
}
