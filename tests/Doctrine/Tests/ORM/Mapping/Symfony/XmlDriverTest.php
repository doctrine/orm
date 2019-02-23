<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\Symfony;

use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use function array_flip;

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
        return new SimplifiedXmlDriver(array_flip($paths));
    }
}
