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
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2175Entity'),
        ));
    }

    public function testIssue()
    {
        $entity = new DDC2175Entity();
        $entity->field = "foo";

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->assertEquals(1, $entity->version);

        $entity->field = "bar";
        $this->_em->flush();

        $this->assertEquals(2, $entity->version);

        $entity->field = "baz";
        $this->_em->flush();

        $this->assertEquals(3, $entity->version);
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
