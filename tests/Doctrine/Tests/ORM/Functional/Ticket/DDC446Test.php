<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC446Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC446Entity'),
        ));
    }

    public function testNullableJsonArrayIsNull()
    {
        $entity = new DDC446Entity();

        $this->_em->persist($entity);

        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(
            DDC446Entity::class,
            $entity->getId()
        );

        $this->assertInstanceOf(DDC446Entity::class, $entity);
        $this->assertNull($entity->getData());
    }
}


/**
 * @Entity
 * @Table(name = "ddc446")
 */
class DDC446Entity
{
    /**
     * @Id
     * @Column(
     *     name = "id",
     *     type = "integer"
     * )
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(
     *     name = "data",
     *     type = "json_array",
     *     nullable = true
     * )
     */
    public $data;

    public function getId()
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }
}
