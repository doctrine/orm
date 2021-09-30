<?php

namespace Doctrine\Tests\Examples;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmTestCase;

class DateTimeExampleTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function setUp(): void
    {
        parent::setup();

        $this->entityManager = $this->getTestEntityManager(['driver' => 'pdo_sqlite', 'memory' => true]);
        $schemaTool = new SchemaTool($this->entityManager);

        $cmf = $this->entityManager->getMetadataFactory();
        $classes = $cmf->getMetadataFor(DateTimeInstance::class);

        $schemaTool->dropDatabase();
        $schemaTool->createSchema([$classes]);
    }

    public function testDateTimeWithTimezoneExample(): void
    {
        $entity = new DateTimeInstance(
            new DateTimeImmutable('2021-09-30 12:00:00', new DateTimeZone('Europe/Busingen')),
            1
        );

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->entityManager->clear();
        $this->entityManager->getUnitOfWork()->clear();

        $newEntity = $this->entityManager->find(DateTimeInstance::class, 1);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEmpty($this->entityManager->getUnitOfWork()->getEntityChangeSet($newEntity));
        $this->assertEquals('Europe/Busingen', $newEntity->getEventDateTime()->getTimezone()->getName());
        $this->assertEquals('2021-09-30 12:00:00', $newEntity->getEventDateTime()->format('Y-m-d H:i:s'));

        $newEntity->convertToUtc();

        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertNotEmpty($this->entityManager->getUnitOfWork()->getEntityChangeSet($newEntity));
    }
}
