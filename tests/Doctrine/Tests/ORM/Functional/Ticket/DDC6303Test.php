<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC6303\DDC6303ContractA;
use Doctrine\Tests\Models\DDC6303\DDC6303ContractB;
use Doctrine\Tests\Models\DDC6303\DDC6303Contract;

/**
 * @group DDC6303
 */
class DDC6303Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('ddc6303');
        parent::setUp();
    }
    
    public function testMixedTypeHydratedCorrectlyInJoinedInheritance()
    {
        $contractA = new DDC6303ContractA();
        $contractAData = 'authorized';
        $contractA->originalData = $contractAData;

        $contractB = new DDC6303ContractB();
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
