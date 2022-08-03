<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Version;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

use function date;
use function strtotime;

class GH8499Test extends OrmFunctionalTestCase
{
    /** @var Connection */
    protected $conn;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [$this->_em->getClassMetadata(GH8499VersionableEntity::class)]
            );
        } catch (Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }

        $this->conn = $this->_em->getConnection();
    }

    /**
     * @group GH-8499
     */
    public function testOptimisticTimestampSetsDefaultValue(): GH8499VersionableEntity
    {
        $entity = new GH8499VersionableEntity();
        $entity->setName('Test Entity');
        $entity->setDescription('Entity to test optimistic lock fix with DateTimeInterface objects');
        self::assertNull($entity->getRevision(), 'Pre-Condition');

        $this->_em->persist($entity);
        $this->_em->flush();

        self::assertInstanceOf(DateTimeInterface::class, $entity->getRevision());

        return $entity;
    }

    /**
     * @group GH-8499
     * @depends testOptimisticTimestampSetsDefaultValue
     */
    public function testOptimisticLockWithDateTimeForVersion(GH8499VersionableEntity $entity): void
    {
        $q = $this->_em->createQuery('SELECT t FROM Doctrine\Tests\ORM\Functional\Ticket\GH8499VersionableEntity t WHERE t.id = :id');
        $q->setParameter('id', $entity->id);
        $test = $q->getSingleResult();

        $format       = $this->_em->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $modifiedDate = new DateTime(date($format, strtotime($test->getRevision()->format($format)) - 3600));

        $this->conn->executeQuery('UPDATE GH8499VersionableEntity SET revision = ? WHERE id = ?', [$modifiedDate->format($format), $test->id]);

        $this->_em->refresh($test);
        $this->_em->lock($test, LockMode::OPTIMISTIC, $modifiedDate);

        $test->setName('Test Entity Locked');
        $this->_em->persist($test);
        $this->_em->flush();

        self::assertEquals('Test Entity Locked', $test->getName(), 'Entity not modified after persist/flush,');
        self::assertGreaterThan($modifiedDate->getTimestamp(), $test->getRevision()->getTimestamp(), 'Current version timestamp is not greater than previous one.');
    }

    /**
     * @group GH-8499
     */
    public function testOptimisticLockWithDateTimeForVersionThrowsException(): void
    {
        $entity = new GH8499VersionableEntity();
        $entity->setName('Test Entity');
        $entity->setDescription('Entity to test optimistic lock fix with DateTimeInterface objects');
        $this->_em->persist($entity);
        $this->_em->flush();

        $this->expectException(OptimisticLockException::class);
        $this->_em->lock($entity, LockMode::OPTIMISTIC, new DateTime('2020-07-15 18:04:00'));
    }
}

/**
 * @Entity
 * @Table
 */
class GH8499VersionableEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @Column(type="string")
     * @var string
     */
    public $name;

    /**
     * @Column(type="string")
     * @var string
     */
    public $description;

    /**
     * @Version
     * @Column(type="datetime")
     * @var DateTimeInterface
     */
    public $revision;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getRevision(): ?DateTimeInterface
    {
        return $this->revision;
    }
}
