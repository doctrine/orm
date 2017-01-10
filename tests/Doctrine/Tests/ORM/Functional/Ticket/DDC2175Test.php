<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-2175
 */
class DDC2175Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [$this->em->getClassMetadata(DDC2175Entity::class)]
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->schemaTool->dropSchema(
            [$this->em->getClassMetadata(DDC2175Entity::class)]
        );
    }

    public function testIssue()
    {
        $entity = new DDC2175Entity();
        $entity->field = "foo";

        $this->em->persist($entity);
        $this->em->flush();

        self::assertEquals(1, $entity->version);

        $entity->field = "bar";
        $this->em->flush();

        self::assertEquals(2, $entity->version);

        $entity->field = "baz";
        $this->em->flush();

        self::assertEquals(3, $entity->version);
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({"entity": "DDC2175Entity"})
 */
class DDC2175Entity
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $field;

    /**
     * @Version
     * @Column(type="integer")
     */
    public $version;
}
