<?php

namespace Doctrine\Tests\ORM\Mapping\Symfony;

use \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;

/**
 * @group DDC-1418
 */
class XmlDriverTest extends AbstractDriverTest
{
    protected function getFileExtension()
    {
        return '.orm.xml';
    }

    protected function getDriver(array $paths = [])
    {
        $driver = new SimplifiedXmlDriver(array_flip($paths));

        return $driver;
    }
}
