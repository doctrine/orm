<?php

namespace Doctrine\Tests\Common;

use Doctrine\Common\GlobalClassLoader,
    Doctrine\Common\IsolatedClassLoader;

require_once __DIR__ . '/../TestInit.php';

class ClassLoaderTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testGlobalClassLoaderThrowsExceptionIfPutInChain()
    {
        $this->setExpectedException('Doctrine\Common\CommonException');

        $classLoader1 = new IsolatedClassLoader('Foo');
        $classLoader1->register();

        $globalClassLoader = new GlobalClassLoader;
        $globalClassLoader->register();
    }

    /*public function testIsolatedClassLoaderReturnsFalseOnClassExists()
    {
        $classLoader = new IsolatedClassLoader('ClassLoaderTest');
        $classLoader->setBasePath( __DIR__);
        $classLoader->setFileExtension('.class.php');
        $classLoader->setNamespaceSeparator('_');

        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassA'), true);
        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassA'), false);
        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassC'), true);
    }*/
}