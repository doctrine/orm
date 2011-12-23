<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1404
 */
class DDC1404Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1404ParentEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1404ChildEntity'),
            ));

            $this->loadFixtures();

        } catch (Exception $exc) {
        }
    }

    public function testTicket()
    {
        $repository     = $this->_em->getRepository(__NAMESPACE__ . '\DDC1404ChildEntity');
        $queryAll       = $repository->createNamedQuery('all');
        $queryFirst     = $repository->createNamedQuery('first');
        $querySecond    = $repository->createNamedQuery('second');


        $this->assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p', $queryAll->getDQL());
        $this->assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p WHERE p.id = 1', $queryFirst->getDQL());
        $this->assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p WHERE p.id = 2', $querySecond->getDQL());


        $this->assertEquals(sizeof($queryAll->getResult()), 2);
        $this->assertEquals(sizeof($queryFirst->getResult()), 1);
        $this->assertEquals(sizeof($querySecond->getResult()), 1);
    }


    public function loadFixtures()
    {
        $c1 = new DDC1404ChildEntity("ChildEntity 1");
        $c2 = new DDC1404ChildEntity("ChildEntity 2");

        $this->_em->persist($c1);
        $this->_em->persist($c2);

        $this->_em->flush();
    }

}

/**
 * @MappedSuperclass
 *
 * @NamedQueries({
 *      @NamedQuery(name="all",     query="SELECT p FROM __CLASS__ p"),
 *      @NamedQuery(name="first",   query="SELECT p FROM __CLASS__ p WHERE p.id = 1"),
 * })
 */
class DDC1404ParentEntity
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue()
     */
    protected $id;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

}

/**
 * @Entity
 *
 * @NamedQueries({
 *      @NamedQuery(name="first",   query="SELECT p FROM __CLASS__ p WHERE p.id = 1"),
 *      @NamedQuery(name="second",  query="SELECT p FROM __CLASS__ p WHERE p.id = 2")
 * })
 */
class DDC1404ChildEntity extends DDC1404ParentEntity
{

    /**
     * @column(type="string")
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}
