<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity;
use Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that an entity with a OneToOne relationship defined as the id, with a version field can be created.
 */
#[Group('VersionedOneToOne')]
class VersionedOneToOneTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            FirstRelatedEntity::class,
            SecondRelatedEntity::class,
        );
    }

    /**
     * This test case tests that a versionable entity, that has a oneToOne relationship as it's id can be created
     *  without this bug fix (DDC-3318), you could not do this
     */
    public function testSetVersionOnCreate(): void
    {
        $secondRelatedEntity       = new SecondRelatedEntity();
        $secondRelatedEntity->name = 'Bob';

        $this->_em->persist($secondRelatedEntity);
        $this->_em->flush();

        $firstRelatedEntity               = new FirstRelatedEntity();
        $firstRelatedEntity->name         = 'Fred';
        $firstRelatedEntity->secondEntity = $secondRelatedEntity;

        $this->_em->persist($firstRelatedEntity);
        $this->_em->flush();

        $firstEntity = $this->_em->getRepository(FirstRelatedEntity::class)
            ->findOneBy(['name' => 'Fred']);

        $secondEntity = $this->_em->getRepository(SecondRelatedEntity::class)
            ->findOneBy(['name' => 'Bob']);

        self::assertSame($firstRelatedEntity, $firstEntity);
        self::assertSame($secondRelatedEntity, $secondEntity);
        self::assertSame($firstEntity->secondEntity, $secondEntity);
    }
}
