<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console;

use Composer\InstalledVersions;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\Tests\DoctrineTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @group DDC-3186
 * @covers \Doctrine\ORM\Tools\Console\ConsoleRunner
 */
final class ConsoleRunnerTest extends DoctrineTestCase
{
    use VerifyDeprecations;

    public function testCreateApplicationShouldReturnAnApplicationWithTheCorrectCommands(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8327');

        $helperSet = new HelperSet();
        $app       = ConsoleRunner::createApplication($helperSet);

        self::assertSame($helperSet, $app->getHelperSet());
        self::assertSame(InstalledVersions::getVersion('doctrine/orm'), $app->getVersion());

        self::assertTrue($app->has('dbal:reserved-words'));
        self::assertTrue($app->has('dbal:run-sql'));
        self::assertTrue($app->has('orm:clear-cache:region:collection'));
        self::assertTrue($app->has('orm:clear-cache:region:entity'));
        self::assertTrue($app->has('orm:clear-cache:region:query'));
        self::assertTrue($app->has('orm:clear-cache:metadata'));
        self::assertTrue($app->has('orm:clear-cache:query'));
        self::assertTrue($app->has('orm:clear-cache:result'));
        self::assertTrue($app->has('orm:convert-d1-schema'));
        self::assertTrue($app->has('orm:convert-mapping'));
        self::assertTrue($app->has('orm:convert:d1-schema'));
        self::assertTrue($app->has('orm:convert:mapping'));
        self::assertTrue($app->has('orm:ensure-production-settings'));
        self::assertTrue($app->has('orm:generate-entities'));
        self::assertTrue($app->has('orm:generate-proxies'));
        self::assertTrue($app->has('orm:generate-repositories'));
        self::assertTrue($app->has('orm:generate:entities'));
        self::assertTrue($app->has('orm:generate:proxies'));
        self::assertTrue($app->has('orm:generate:repositories'));
        self::assertTrue($app->has('orm:info'));
        self::assertTrue($app->has('orm:mapping:describe'));
        self::assertTrue($app->has('orm:run-dql'));
        self::assertTrue($app->has('orm:schema-tool:create'));
        self::assertTrue($app->has('orm:schema-tool:drop'));
        self::assertTrue($app->has('orm:schema-tool:update'));
        self::assertTrue($app->has('orm:validate-schema'));
    }

    public function testCreateApplicationShouldAppendGivenCommands(): void
    {
        $command = 'my:lovely-command';
        $app     = ConsoleRunner::createApplication(new HelperSet(), [new Command($command)]);

        self::assertTrue($app->has($command));
    }

    public function testCreateApplicationWithProvider(): void
    {
        $provider = $this->createMock(EntityManagerProvider::class);
        $app      = ConsoleRunner::createApplication($provider, []);

        self::assertTrue($app->has('orm:info'));
    }
}
