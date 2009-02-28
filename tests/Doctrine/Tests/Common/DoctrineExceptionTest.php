<?php

namespace Doctrine\Tests\Common;

class DoctrineExceptionTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testStaticCall()
    {
        $e = \Doctrine\Common\DoctrineException::testingStaticCallBuildsErrorMessageWithParams('param1', 'param2');

        $this->assertEquals($e->getMessage(), "Testing static call builds error message with params ('param1', 'param2')");
    }
}