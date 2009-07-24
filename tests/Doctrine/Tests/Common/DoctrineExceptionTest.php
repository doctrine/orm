<?php

namespace Doctrine\Tests\Common;

require_once __DIR__ . '/../TestInit.php';

class DoctrineExceptionTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testStaticCall()
    {
        $e = \Doctrine\Common\DoctrineException::testingStaticCallBuildsErrorMessageWithParams('param1', 'param2');

        $this->assertEquals($e->getMessage(), "Testing static call builds error message with params ('param1', 'param2')");
    }

    public function testInnerException()
    {
        $e1 = \Doctrine\Common\DoctrineException::testException();
        $e2 = \Doctrine\Common\DoctrineException::testException2('param1', $e1);
        $this->assertEquals($e1, $e2->getPrevious());
    }

    public function testNotImplemented()
    {
        $e = \Doctrine\Common\DoctrineException::notImplemented('testMethod', 'SomeClass');
        $this->assertEquals("The method 'testMethod' is not implemented in class 'SomeClass'.", $e->getMessage());
    }

    public function testGetExceptionMessage()
    {
        $this->assertEquals('The query contains more than one result.', \Doctrine\Common\DoctrineException::getExceptionMessage('QueryException#nonUniqueResult'));
    }

    public function testUseGetExceptionMessage()
    {
        $q = \Doctrine\ORM\Query\QueryException::nonUniqueResult();
        $this->assertEquals('The query contains more than one result.', $q->getMessage());
    }
}