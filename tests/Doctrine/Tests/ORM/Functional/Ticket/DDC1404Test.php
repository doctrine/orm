<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1404
 */
class DDC1404Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1404ParentEntity::class),
                $this->em->getClassMetadata(DDC1404ChildEntity::class),
                ]
            );

            $this->loadFixtures();

        } catch (Exception $exc) {
        }
    }

    public function testTicket()
    {
        $repository     = $this->em->getRepository(DDC1404ChildEntity::class);
        $queryAll       = $repository->createNamedQuery('all');
        $queryFirst     = $repository->createNamedQuery('first');
        $querySecond    = $repository->createNamedQuery('second');


        self::assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p', $queryAll->getDQL());
        self::assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p WHERE p.id = 1', $queryFirst->getDQL());
        self::assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p WHERE p.id = 2', $querySecond->getDQL());


        self::assertEquals(sizeof($queryAll->getResult()), 2);
        self::assertEquals(sizeof($queryFirst->getResult()), 1);
        self::assertEquals(sizeof($querySecond->getResult()), 1);
    }


    public function loadFixtures()
    {
        $c1 = new DDC1404ChildEntity("ChildEntity 1");
        $c2 = new DDC1404ChildEntity("ChildEntity 2");

        $this->em->persist($c1);
        $this->em->persist($c2);

        $this->em->flush();
    }

}

/**
 * @ORM\MappedSuperclass
 *
 * @ORM\NamedQueries({
 *      @ORM\NamedQuery(name="all",     query="SELECT p FROM __CLASS__ p"),
 *      @ORM\NamedQuery(name="first",   query="SELECT p FROM __CLASS__ p WHERE p.id = 1"),
 * })
 */
class DDC1404ParentEntity
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

}

/**
 * @ORM\Entity
 *
 * @ORM\NamedQueries({
 *      @ORM\NamedQuery(name="first",   query="SELECT p FROM __CLASS__ p WHERE p.id = 1"),
 *      @ORM\NamedQuery(name="second",  query="SELECT p FROM __CLASS__ p WHERE p.id = 2")
 * })
 */
class DDC1404ChildEntity extends DDC1404ParentEntity
{

    /**
     * @ORM\Column(type="string")
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
