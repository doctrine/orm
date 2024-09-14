<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\Tests\OrmTestCase;
use Symfony\Component\Console\Output\OutputInterface;

use function sys_get_temp_dir;

class ConvertDoctrine1SchemaCommandTest extends OrmTestCase
{
    public function testExecution(): void
    {
        $entityGenerator = $this->createMock(EntityGenerator::class);
        $command         = new ConvertDoctrine1SchemaCommand();
        $command->setEntityGenerator($entityGenerator);

        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::once())
               ->method('writeln')
               ->with(self::equalTo('No Metadata Classes to process.'));

        $command->convertDoctrine1Schema([], sys_get_temp_dir(), 'annotation', 4, null, $output);
    }
}
