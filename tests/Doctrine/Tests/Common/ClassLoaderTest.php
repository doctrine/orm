<?php

namespace Doctrine\Tests\Common;

use Doctrine\Common\ClassLoader;

require_once __DIR__ . '/../TestInit.php';

class ClassLoaderTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testClassLoader()
    {
        $classLoader = new ClassLoader('ClassLoaderTest');
        $classLoader->setIncludePath(__DIR__);
        $classLoader->setFileExtension('.class.php');
        $classLoader->setNamespaceSeparator('_');

        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassA'), true);
        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassB'), true);
        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassC'), true);
    }
}