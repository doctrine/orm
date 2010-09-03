<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand;

class ConvertDoctrine1SchemaCommandTest extends \Doctrine\Tests\OrmTestCase
{
    public function testExecution()
    {
        $entityGenerator = $this->getMock('Doctrine\ORM\Tools\EntityGenerator');
        $metadataExporter = $this->getMock('Doctrine\ORM\Tools\Export\ClassMetadataExporter');
        $command = new ConvertDoctrine1SchemaCommand();
        $command->setEntityGenerator($entityGenerator);

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $output->expects($this->once())
               ->method('write')
               ->with($this->equalTo('No Metadata Classes to process.' . PHP_EOL));

        $command->convertDoctrine1Schema($this->_getTestEntityManager(), array(), sys_get_temp_dir(), 'annotation', 4, null, $output);
    }
}