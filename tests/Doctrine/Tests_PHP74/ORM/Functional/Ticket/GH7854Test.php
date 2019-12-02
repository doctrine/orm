<?php

declare(strict_types=1);

namespace Doctrine\Tests_PHP74\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH7854
 */
class GH7854Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH7854TestEntity::class),
                $this->_em->getClassMetadata(GH7854ValueObject::class),
            ]
        );
    }

    public function testTypedPropertyContainingEmbeddable() : void
    {
        $entity = new GH7854TestEntity();
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        /** @var GH7854TestEntity[] $entities */
        $entities = $this->_em->getRepository(GH7854TestEntity::class)->findAll();

        self::assertEquals($entity, $entities[0]);
    }
}

/**
 * @Entity()
 */
class GH7854TestEntity
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue
     */
    private int $id;

    /**
     * @Embedded(class=GH7854ValueObject::class)
     */
    private ?GH7854ValueObject $valueObject;
}

/**
 * @Embeddable()
 */
class GH7854ValueObject
{
    /** @Column(name="value", type="integer") */
    public int $value;
}
