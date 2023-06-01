<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function uniqid;

/**
 * @group GH7877
 */
class GH7877Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH7877ApplicationGenerated::class,
            GH7877DatabaseGenerated::class
        );
    }

    public function testNotNullableColumnWorksWithApplicationGeneratedId(): void
    {
        $entity         = new GH7877ApplicationGenerated($entityId = uniqid());
        $entity->parent = $entity;
        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();
        $child = $this->_em->find(GH7877ApplicationGenerated::class, $entityId);
        self::assertSame($entityId, $child->parent->id);
    }

    public function testNullableColumnWorksWithDatabaseGeneratedId(): void
    {
        $entity         = new GH7877DatabaseGenerated();
        $entity->parent = $entity;
        $this->_em->persist($entity);

        $this->_em->flush();

        $entityId = $entity->id;
        $this->_em->clear();

        $child = $this->_em->find(GH7877DatabaseGenerated::class, $entityId);
        self::assertSame($entityId, $child->parent->id);
    }
}

/**
 * @ORM\Entity
 */
class GH7877ApplicationGenerated
{
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var string
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\GH7877ApplicationGenerated")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var self
     */
    public $parent;
}

/**
 * @ORM\Entity
 */
class GH7877DatabaseGenerated
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\GH7877DatabaseGenerated")
     *
     * @var self
     */
    public $parent;
}
