<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group #6303
 */
class DDC6303Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema([
                $this->_em->getClassMetadata(DDC6303BaseClass::class),
                $this->_em->getClassMetadata(DDC6303ChildA::class),
                $this->_em->getClassMetadata(DDC6303ChildB::class),
            ]);
        } catch (ToolsException $ignored) {
        }
    }

    public function testMixedTypeHydratedCorrectlyInJoinedInheritance() : void
    {
        // DDC6303ChildA and DDC6303ChildB have an inheritance from DDC6303BaseClass,
        // but one has a string originalData and the second has an array, since the fields
        // are mapped differently
        self::assertHydratedEntitiesSameToPersistedOnes([
            'a' => new DDC6303ChildA('a', 'authorized'),
            'b' => new DDC6303ChildB('b', ['accepted', 'authorized']),
        ]);

    }

    public function testEmptyValuesInJoinedInheritance() : void
    {
        self::assertHydratedEntitiesSameToPersistedOnes([
            'stringEmpty' => new DDC6303ChildA('stringEmpty', ''),
            'stringZero'  => new DDC6303ChildA('stringZero', 0),
            'arrayEmpty'  => new DDC6303ChildB('arrayEmpty', []),
        ]);
    }

    /**
     * @param DDC6303BaseClass[] $persistedEntities indexed by identifier
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function assertHydratedEntitiesSameToPersistedOnes(array $persistedEntities) : void
    {
        array_walk($persistedEntities, [$this->_em, 'persist']);
        $this->_em->flush();
        $this->_em->clear();

        /* @var $entities DDC6303BaseClass[] */
        $entities = $this
            ->_em
            ->getRepository(DDC6303BaseClass::class)
            ->createQueryBuilder('p')
            ->where('p.id IN(:ids)')
            ->setParameter('ids', array_keys($persistedEntities))
            ->getQuery()->getResult();

        self::assertCount(count($persistedEntities), $entities);

        foreach ($entities as $entity) {
            self::assertEquals($entity, $persistedEntities[$entity->id]);
        }
    }
}

/**
 * @Entity
 * @Table
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      DDC6303ChildB::class = DDC6303ChildB::class,
 *      DDC6303ChildA::class = DDC6303ChildA::class,
 * })
 *
 * Note: discriminator map order *IS IMPORTANT* for this test
 */
abstract class DDC6303BaseClass
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    public $id;
}

/** @Entity @Table */
class DDC6303ChildA extends DDC6303BaseClass
{
    /** @Column(type="string") */
    private $originalData;

    public function __construct(string $id, $originalData)
    {
        $this->id           = $id;
        $this->originalData = $originalData;
    }
}

/** @Entity @Table */
class DDC6303ChildB extends DDC6303BaseClass
{
    /** @Column(type="simple_array", nullable=true) */
    private $originalData;

    public function __construct(string $id, array $originalData)
    {
        $this->id           = $id;
        $this->originalData = $originalData;
    }
}
