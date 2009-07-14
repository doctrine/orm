<?php

namespace Doctrine\Tests\Common;

use Doctrine\Common\ClassLoader;

require_once __DIR__ . '/../TestInit.php';

class ClassLoaderTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testCustomFileExtensionAndNamespaceSeparator()
    {
        $classLoader = new \Doctrine\Common\ClassLoader();
        $classLoader->setBasePath('ClassLoaderTest', __DIR__);
        $classLoader->setClassFileExtension('.class.php');
        $classLoader->setNamespaceSeparator('_');

        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassA'), true);
        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassB'), true);
    }

    public function testClassLoaderCheckFileExists()
    {
        $classLoader = new \Doctrine\Common\ClassLoader();
        $classLoader->setBasePath('ClassLoaderTest', __DIR__);
        $classLoader->setCheckFileExists(true);

        // This would return a fatal error without check file exists true
        $this->assertEquals($classLoader->loadClass('SomeInvalidClass'), false);
    }

    public function testAlreadyLoadedClassReturnsFalse()
    {
        $classLoader = new \Doctrine\Common\ClassLoader();
        $classLoader->setBasePath('ClassLoaderTest', __DIR__);
        $classLoader->setClassFileExtension('.class.php');
        $classLoader->setNamespaceSeparator('_');
        $classLoader->setCheckFileExists(true);

        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassA'), false);
        $this->assertEquals($classLoader->loadClass('ClassLoaderTest_ClassC'), true);
    }
}