<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC2161Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    /**
     * @group DDC-2161
     */
    public function testSchemaUpdateOnForeignKeyMove()
    {
        $classes = array(
          $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC2161_1')
        );
        $this->_schemaTool->createSchema($classes);

        $classes = array(
          $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC2161_2'),
          $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC2161_3')
        );
        $this->_schemaTool->updateSchema($classes,true);

        $sm = $this->_em->getConnection()->getSchemaManager();
        $schema = $sm->createSchema();

        $fk = $schema->getTable("DDC2161_1")->getForeignKeys();
        $fk = array_shift($fk);

        $this->assertEquals("DDC2161_3", $fk->getForeignTableName(), "Foreign key table should be DDC2161_3.");
    }
}

/**
 * @Entity
 */
class DDC2161_1
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    private $id;

    /**
     * @OneToMany(targetEntity="DDC2161_1", mappedBy="parent")
     */
    private $children;

    /**
     * @ManyToOne(targetEntity="DDC2161_1", inversedBy="children")
     */
    private $parent;
}

/**
 * @Table("DDC2161_1")
 * @Entity
 */
class DDC2161_2
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC2161_3", inversedBy="children")
     */
    private $parent;
}

/**
 * @Entity
 */
class DDC2161_3
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    private $id;

    /**
     * @OneToMany(targetEntity="DDC2161_2", mappedBy="parent")
     */
    private $children;
}