<?php

namespace Doctrine\Tests\DBAL\Functional;

use Doctrine\DBAL\ConnectionException;

require_once __DIR__ . '/../../TestInit.php';

class ConnectionTest extends \Doctrine\Tests\DbalFunctionalTestCase
{
    
    public function testTransactionNestingBehavior()
    {
        try {
            $this->_conn->beginTransaction();
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            
            try {
                $this->_conn->beginTransaction();
                $this->assertEquals(2, $this->_conn->getTransactionNestingLevel());
                throw new \Exception;
                $this->_conn->commit(); // never reached
            } catch (\Exception $e) {
                $this->_conn->rollback();
                $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
                //no rethrow                
            }
            $this->assertTrue($this->_conn->getRollbackOnly());
              
            $this->_conn->commit(); // should throw exception
            $this->fail('Transaction commit after failed nested transaction should fail.');
        } catch (ConnectionException $e) {
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            $this->_conn->rollback();
            $this->assertEquals(0, $this->_conn->getTransactionNestingLevel());
        }
    }
    
    public function testTransactionBehavior()
    {
        try {
            $this->_conn->beginTransaction();
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            
            throw new \Exception;
              
            $this->_conn->commit(); // never reached
        } catch (\Exception $e) {
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            $this->_conn->rollback();
            $this->assertEquals(0, $this->_conn->getTransactionNestingLevel());
        }
        
        try {
            $this->_conn->beginTransaction();
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            $this->_conn->commit();
        } catch (\Exception $e) {
            $this->_conn->rollback();
            $this->assertEquals(0, $this->_conn->getTransactionNestingLevel());
        }
    }
    
}