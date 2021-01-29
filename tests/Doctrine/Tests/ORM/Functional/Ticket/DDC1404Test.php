<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

use function sizeof;

/**
 * @group DDC-1404
 */
class DDC1404Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DDC1404ParentEntity::class),
                    $this->_em->getClassMetadata(DDC1404ChildEntity::class),
                ]
            );

            $this->loadFixtures();
        } catch (Exception $exc) {
        }
    }

    public function testTicket(): void
    {
        $repository  = $this->_em->getRepository(DDC1404ChildEntity::class);
        $queryAll    = $repository->createNamedQuery('all');
        $queryFirst  = $repository->createNamedQuery('first');
        $querySecond = $repository->createNamedQuery('second');

        $this->assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p', $queryAll->getDQL());
        $this->assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p WHERE p.id = 1', $queryFirst->getDQL());
        $this->assertEquals('SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1404ChildEntity p WHERE p.id = 2', $querySecond->getDQL());

        $this->assertEquals(sizeof($queryAll->getResult()), 2);
        $this->assertEquals(sizeof($queryFirst->getResult()), 1);
        $this->assertEquals(sizeof($querySecond->getResult()), 1);
    }

    public function loadFixtures(): void
    {
        $c1 = new DDC1404ChildEntity('ChildEntity 1');
        $c2 = new DDC1404ChildEntity('ChildEntity 2');

        $this->_em->persist($c1);
        $this->_em->persist($c2);

        $this->_em->flush();
    }
}

/**
 * @MappedSuperclass
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

    public function getId(): int
    {
        return $this->id;
    }
}

/**
 * @Entity
 * @NamedQueries({
 *      @NamedQuery(name="first",   query="SELECT p FROM __CLASS__ p WHERE p.id = 1"),
 *      @NamedQuery(name="second",  query="SELECT p FROM __CLASS__ p WHERE p.id = 2")
 * })
 */
class DDC1404ChildEntity extends DDC1404ParentEntity
{
    /** @column(type="string") */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
