<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

class SequenceEmulatedIdentityStrategyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConnection()->getDatabasePlatform()->usesSequenceEmulatedIdentityColumns()) {
            self::markTestSkipped(
                'This test is special to platforms emulating IDENTITY key generation strategy through sequences.'
            );
        } else {
            $this->createSchemaForModels(SequenceEmulatedIdentityEntity::class);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $connection = $this->_em->getConnection();
        $platform   = $connection->getDatabasePlatform();

        // drop sequence manually due to dependency
        $connection->executeStatement(
            $platform->getDropSequenceSQL(
                $platform->getIdentitySequenceName('seq_identity', 'id')
            )
        );
    }

    public function testPreSavePostSaveCallbacksAreInvoked(): void
    {
        $entity = new SequenceEmulatedIdentityEntity();
        $entity->setValue('hello');
        $this->_em->persist($entity);
        $this->_em->flush();
        self::assertIsNumeric($entity->getId());
        self::assertGreaterThan(0, $entity->getId());
        self::assertTrue($this->_em->contains($entity));
    }
}

/**
 * @Entity
 * @Table(name="seq_identity")
 */
class SequenceEmulatedIdentityEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    private $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
