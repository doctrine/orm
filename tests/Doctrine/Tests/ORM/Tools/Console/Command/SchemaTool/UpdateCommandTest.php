<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;

class UpdateCommandTest extends AbstractCommandTest
{
    public function testItPrintsTheSql(): void
    {
        $tester = $this->getCommandTester(UpdateCommand::class);
        $tester->execute(['--dump-sql' => true]);
        self::assertStringContainsString('CREATE TABLE keyboard', $tester->getDisplay());
    }
}
