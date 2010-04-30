<?php

namespace Doctrine\Tests\DBAL\Functional;

use Doctrine\DBAL\ConnectionException;

require_once __DIR__ . '/../../TestInit.php';

class TransactionTest extends \Doctrine\Tests\DbalFunctionalTestCase
{
    
    public function testTransactionNestingBehavior()
    {
        $tx = $this->_conn->getTransaction();

        try {
            $tx->begin();
            $this->assertEquals(1, $tx->getTransactionNestingLevel());
            
            try {
                $tx->begin();
                $this->assertEquals(2, $tx->getTransactionNestingLevel());
                throw new \Exception;
                $tx->commit(); // never reached
            } catch (\Exception $e) {
                $tx->rollback();
                $this->assertEquals(1, $tx->getTransactionNestingLevel());
                //no rethrow                
            }
            $this->assertTrue($tx->getRollbackOnly());
              
            $tx->commit(); // should throw exception
            $this->fail('Transaction commit after failed nested transaction should fail.');
        } catch (ConnectionException $e) {
            $this->assertEquals(1, $tx->getTransactionNestingLevel());
            $tx->rollback();
            $this->assertEquals(0, $tx->getTransactionNestingLevel());
        }
    }
    
    public function testTransactionBehavior()
    {
        $tx = $this->_conn->getTransaction();

        try {
            $tx->begin();
            $this->assertEquals(1, $tx->getTransactionNestingLevel());
            
            throw new \Exception;
              
            $tx->commit(); // never reached
        } catch (\Exception $e) {
            $this->assertEquals(1, $tx->getTransactionNestingLevel());
            $tx->rollback();
            $this->assertEquals(0, $tx->getTransactionNestingLevel());
        }
        
        try {
            $tx->begin();
            $this->assertEquals(1, $tx->getTransactionNestingLevel());
            $tx->commit();
        } catch (\Exception $e) {
            $tx->rollback();
            $this->assertEquals(0, $tx->getTransactionNestingLevel());
        }
    }
    
}