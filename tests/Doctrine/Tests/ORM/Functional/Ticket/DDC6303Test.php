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

        $contracts = $repository->createQueryBuilder('p')
            ->getQuery()->getResult();

        foreach( $contracts as $contract ){
            switch( $contract->id ){
                case $contractA->id:
                    static::assertEquals($contract->originalData, $contractAData);
                    break;

                case $contractB->id:
                    static::assertEquals($contract->originalData, $contractBData);
                    break;
            }
        }
     }
}
