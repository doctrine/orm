<?php

namespace Shitty\Tests\ORM\Tools\Console;

use Shitty\ORM\Tools\Console\ConsoleRunner;
use Shitty\ORM\Version;
use Shitty\Tests\DoctrineTestCase;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @group DDC-3186
 *
 * @covers \Doctrine\ORM\Tools\Console\ConsoleRunner
 */
class ConsoleRunnerTest extends DoctrineTestCase
{
    public function testCreateApplication()
    {
        $helperSet = new HelperSet();
        $app       = ConsoleRunner::createApplication($helperSet);

        $this->assertInstanceOf('Symfony\Component\Console\Application', $app);
        $this->assertSame($helperSet, $app->getHelperSet());
        $this->assertEquals(Version::VERSION, $app->getVersion());
    }
}
