<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC6303
 */
class DDC6303Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC6303Contract::class),
                $this->_em->getClassMetadata(DDC6303ContractA::class),
                $this->_em->getClassMetadata(DDC6303ContractB::class)
                ]
            );
        } catch (\Exception $ignored) {}
    }
    
    public function testMixedTypeHydratedCorrectlyInJoinedInheritance()
    {
        $contractA = new DDC6303ContractA();
        $contractAData = 'authorized';
        $contractA->originalData = $contractAData;

        $contractB = new DDC6303ContractB();
        //contractA and contractB have an inheritance from Contract, but one has a string originalData and the second has an array
        $contractBData = ['accepted', 'authorized'];
        $contractB->originalData = $contractBData;

        $this->_em->persist($contractA);
        $this->_em->persist($contractB);

        $this->_em->flush();
        
        // clear entity manager so that $repository->find actually fetches them and uses the hydrator
        // instead of just returning the existing managed entities
        $this->_em->clear();

        $repository = $this->_em->getRepository(DDC6303Contract::class);               

        $dataMap = [
            $contractA->id => $contractAData,
            $contractB->id => $contractBData
        ];              

        $contracts = $repository->createQueryBuilder('p')
            ->where('p.id IN(:ids)')
            ->setParameter('ids', array_keys($dataMap))
            ->getQuery()->getResult(); 

        foreach( $contracts as $contract ){
            static::assertEquals($contract->originalData, $dataMap[$contract->id], 'contract ' . get_class($contract) . ' not equals to original');
        }
     }

     public function testEmptyValuesInJoinedInheritance()
    {
        $contractStringEmptyData = '';
        $contractStringZeroData = 0;
        $contractArrayEmptyData = [];        

        $contractStringEmpty = new DDC6303ContractA();
        $contractStringEmpty->originalData = $contractStringEmptyData;

        $contractStringZero = new DDC6303ContractA();
        $contractStringZero->originalData = $contractStringZeroData;

        $contractArrayEmpty = new DDC6303ContractB();
        $contractArrayEmpty->originalData = $contractArrayEmptyData;

        $this->_em->persist($contractStringZero);
        $this->_em->persist($contractStringEmpty);
        $this->_em->persist($contractArrayEmpty);

        $this->_em->flush();
        
        // clear entity manager so that $repository->find actually fetches them and uses the hydrator
        // instead of just returning the existing managed entities
        $this->_em->clear();

        $repository = $this->_em->getRepository(DDC6303Contract::class); 
        $dataMap = [
            $contractStringZero->id => $contractStringZeroData,
            $contractStringEmpty->id => $contractStringEmptyData,
            $contractArrayEmpty->id => $contractArrayEmptyData,
        ];              

        $contracts = $repository->createQueryBuilder('p')
            ->where('p.id IN(:ids)')
            ->setParameter('ids', array_keys($dataMap))
            ->getQuery()->getResult(); 

        foreach( $contracts as $contract ){
            static::assertEquals($contract->originalData, $dataMap[$contract->id], 'contract ' . get_class($contract) . ' not equals to original');
        }
     }
}


/**
 * @Entity
 * @Table(name="ddc6303_contract")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "contract"    = "DDC6303Contract",
 *      "contract_b"  = "DDC6303ContractB",
 *      "contract_a"  = "DDC6303ContractA"
 * })
 */
class DDC6303Contract
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
}


/**
 * @Entity
 * @Table(name="ddc6303_contracts_a")
 */
class DDC6303ContractA extends DDC6303Contract
{
    /**
     * @Column(type="string", nullable=true)
     *
     * @var string
     */
    public $originalData;
}


/**
 * @Entity
 * @Table(name="ddc6303_contracts_b")
 */
class DDC6303ContractB extends DDC6303Contract
{
    /**
     * @Column(type="simple_array", nullable=true)
     *
     * @var array
     */
    public $originalData;
}
