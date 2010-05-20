<?php

namespace Doctrine\Tests\DBAL\Functional;

use Doctrine\DBAL\ConnectionException;

require_once __DIR__ . '/../../TestInit.php';

class ConnectionTest extends \Doctrine\Tests\DbalFunctionalTestCase
{
    public function setUp()
    {
        $this->resetSharedConn();
        parent::setUp();
    }

    public function testGetWrappedConnection()
    {
        $this->assertType('Doctrine\DBAL\Driver\Connection', $this->_conn->getWrappedConnection());
    }

    public function testCommitWithRollbackOnlyThrowsException()
    {
        $this->_conn->beginTransaction();
        $this->_conn->setRollbackOnly();
        $this->setExpectedException('Doctrine\DBAL\ConnectionException');
        $this->_conn->commit();
    }

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
            $this->assertTrue($this->_conn->isRollbackOnly());
              
            $this->_conn->commit(); // should throw exception
            $this->fail('Transaction commit after failed nested transaction should fail.');
        } catch (ConnectionException $e) {
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            $this->_conn->rollback();
            $this->assertEquals(0, $this->_conn->getTransactionNestingLevel());
        }
    }
    
    public function testTransactionBehaviorWithRollback()
    {
        try {
            $this->_conn->beginTransaction();
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            
            throw new \Exception;
              
            $this->_connx->commit(); // never reached
        } catch (\Exception $e) {
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            $this->_conn->rollback();
            $this->assertEquals(0, $this->_conn->getTransactionNestingLevel());
        }
    }

    public function testTransactionBehaviour()
    {
        try {
            $this->_conn->beginTransaction();
            $this->assertEquals(1, $this->_conn->getTransactionNestingLevel());
            $this->_conn->commit();
        } catch (\Exception $e) {
            $this->_conn->rollback();
            $this->assertEquals(0, $this->_conn->getTransactionNestingLevel());
        }

        $this->assertEquals(0, $this->_conn->getTransactionNestingLevel());
    }

    public function testTransactionalWithException()
    {
        try {
            $this->_conn->transactional(function($conn) {
                $conn->executeQuery("select 1");
                throw new \RuntimeException("Ooops!");
            });
        } catch (\RuntimeException $expected) {
            $this->assertEquals(0, $this->_conn->getTransactionNestingLevel());
        }
    }

    public function testTransactional()
    {
        $this->_conn->transactional(function($conn) {
            $conn->executeQuery("select 1");
        });
    }
}