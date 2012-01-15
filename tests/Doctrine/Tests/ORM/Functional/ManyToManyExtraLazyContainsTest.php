<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
require_once __DIR__ . '/../../TestInit.php';

/**
 * 
 */
class ManyToManyExtraLazyContainsTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testManyToManyExtraLazyContainsAddedPendingInsertEntityIsTrue()
    {
        $contract = new \Doctrine\Tests\Models\Company\CompanyFlexContract();

        $this->_em->persist($contract);
        $this->_em->flush();
        
        $this->_em->clear();
        $contract = $this->_em->find('Doctrine\Tests\Models\Company\CompanyFlexContract', $contract->getId());
        
        $pendingInsertManager = new \Doctrine\Tests\Models\Company\CompanyManager();
        $this->_em->persist($pendingInsertManager);
        $contract->getManagers()->add($pendingInsertManager);
        
        $result = $contract->getManagers()->contains($pendingInsertManager);

        $this->assertTrue($result);
    }

    public function testManyToManyExtraLazyContainsNonAddedPendingInsertEntityIsFalse()
    {
        $contract = new \Doctrine\Tests\Models\Company\CompanyFlexContract();

        $this->_em->persist($contract);
        $this->_em->flush();
        
        $this->_em->clear();
        $contract = $this->_em->find('Doctrine\Tests\Models\Company\CompanyFlexContract', $contract->getId());
        
        $pendingInsertManager = new \Doctrine\Tests\Models\Company\CompanyManager();
        $this->_em->persist($pendingInsertManager);
        
        $result = $contract->getManagers()->contains($pendingInsertManager);

        $this->assertFalse($result);
    }
}