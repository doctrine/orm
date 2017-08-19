<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group #6303
 */
class DDC6303Test extends OrmFunctionalTestCase
{
    public function setUp()
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

    public function testMixedTypeHydratedCorrectlyInJoinedInheritance()
    {
        $a = new DDC6303ChildA();
        $b = new DDC6303ChildB();

        $aData = 'authorized';
        $bData = ['accepted', 'authorized'];

        // DDC6303ChildA and DDC6303ChildB have an inheritance from DDC6303BaseClass,
        // but one has a string originalData and the second has an array, since the fields
        // are mapped differently
        $a->originalData = $aData;
        $b->originalData = $bData;

        $this->_em->persist($a);
        $this->_em->persist($b);

        $this->_em->flush();

        // clear entity manager so that $repository->find actually fetches them and uses the hydrator
        // instead of just returning the existing managed entities
        $this->_em->clear();

        $repository = $this->_em->getRepository(DDC6303BaseClass::class);

        $dataMap = [
            $a->id => $aData,
            $b->id => $bData,
        ];

        $contracts = $repository
            ->createQueryBuilder('p')
            ->where('p.id IN(:ids)')
            ->setParameter('ids', array_keys($dataMap))
            ->getQuery()->getResult();

        foreach ($contracts as $contract) {
            static::assertEquals(
                $contract->originalData,
                $dataMap[$contract->id],
                'contract ' . get_class($contract) . ' not equals to original'
            );
        }
    }

    public function testEmptyValuesInJoinedInheritance()
    {
        $contractStringEmptyData = '';
        $contractStringZeroData  = 0;
        $contractArrayEmptyData  = [];

        $contractStringEmpty = new DDC6303ChildA();
        $contractStringZero  = new DDC6303ChildA();
        $contractArrayEmpty  = new DDC6303ChildB();

        $contractStringEmpty->originalData = $contractStringEmptyData;
        $contractStringZero->originalData  = $contractStringZeroData;
        $contractArrayEmpty->originalData  = $contractArrayEmptyData;

        $this->_em->persist($contractStringZero);
        $this->_em->persist($contractStringEmpty);
        $this->_em->persist($contractArrayEmpty);

        $this->_em->flush();

        // clear entity manager so that $repository->find actually fetches them and uses the hydrator
        // instead of just returning the existing managed entities
        $this->_em->clear();

        $repository = $this->_em->getRepository(DDC6303BaseClass::class);
        $dataMap    = [
            $contractStringZero->id  => $contractStringZeroData,
            $contractStringEmpty->id => $contractStringEmptyData,
            $contractArrayEmpty->id  => $contractArrayEmptyData,
        ];

        /* @var $contracts DDC6303ChildA[]|DDC6303ChildB[] */
        $contracts = $repository
            ->createQueryBuilder('p')
            ->where('p.id IN(:ids)')
            ->setParameter('ids', array_keys($dataMap))
            ->getQuery()
            ->getResult();

        foreach ($contracts as $contract) {
            static::assertEquals(
                $contract->originalData,
                $dataMap[$contract->id],
                'contract ' . get_class($contract) . ' not equals to original'
            );
        }
    }
}

/**
 * @Entity
 * @Table
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      DDC6303ChildA::class = DDC6303ChildA::class,
 *      DDC6303ChildB::class = DDC6303ChildB::class,
 * })
 */
abstract class DDC6303BaseClass
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/** @Entity @Table */
class DDC6303ChildA extends DDC6303BaseClass
{
    /** @Column(type="string", nullable=true) */
    public $originalData;
}

/** @Entity @Table */
class DDC6303ChildB extends DDC6303BaseClass
{
    /** @Column(type="simple_array", nullable=true) */
    public $originalData;
}
