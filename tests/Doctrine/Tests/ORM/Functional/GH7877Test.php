<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function uniqid;

class GH7877Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH7877ApplicationGeneratedIdEntity::class,
            GH7877EntityWithNullableAssociation::class,
        );
    }

    public function testSelfReferenceWithApplicationGeneratedIdMayBeNotNullable(): void
    {
        $entity         = new GH7877ApplicationGeneratedIdEntity();
        $entity->parent = $entity;

        $this->expectNotToPerformAssertions();

        $this->_em->persist($entity);
        $this->_em->flush();
    }

    public function testCrossReferenceWithApplicationGeneratedIdMayBeNotNullable(): void
    {
        $entity1         = new GH7877ApplicationGeneratedIdEntity();
        $entity1->parent = $entity1;
        $entity2         = new GH7877ApplicationGeneratedIdEntity();
        $entity2->parent = $entity1;

        $this->expectNotToPerformAssertions();

        // As long as we do not have entity-level commit order computation
        // (see https://github.com/doctrine/orm/pull/10547),
        // this only works when the UoW processes $entity1 before $entity2,
        // so that the foreign key constraint E2 -> E1 can be satisfied.

        $this->_em->persist($entity1);
        $this->_em->persist($entity2);
        $this->_em->flush();
    }

    public function testNullableForeignKeysMakeInsertOrderLessRelevant(): void
    {
        $entity1         = new GH7877EntityWithNullableAssociation();
        $entity1->parent = $entity1;
        $entity2         = new GH7877EntityWithNullableAssociation();
        $entity2->parent = $entity1;

        $this->expectNotToPerformAssertions();

        // In contrast to the previous test, this case demonstrates that with NULLable
        // associations, even without entity-level commit order computation
        // (see https://github.com/doctrine/orm/pull/10547), we can get away with an
        // insertion order of E2 before E1. That is because the UoW will schedule an extra
        // update that saves the day - the foreign key reference will established only after
        // all insertions have been performed.

        $this->_em->persist($entity2);
        $this->_em->persist($entity1);
        $this->_em->flush();
    }
}

#[ORM\Entity]
class GH7877ApplicationGeneratedIdEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    /** (!) Note this uses "nullable=false" */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: false)]
    public self $parent;

    public function __construct()
    {
        $this->id = uniqid();
    }
}

#[ORM\Entity]
class GH7877EntityWithNullableAssociation
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true)]
    public self $parent;

    public function __construct()
    {
        $this->id = uniqid();
    }
}
