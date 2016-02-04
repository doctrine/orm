<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand;

class ConvertDoctrine1SchemaCommandTest extends \Doctrine\Tests\OrmTestCase
{
    public function testExecution()
    {
        $entityGenerator = $this->getMock('Doctrine\ORM\Tools\EntityGenerator');
        $command = new ConvertDoctrine1SchemaCommand();
        $command->setEntityGenerator($entityGenerator);

        $output = $this->getMockBuilder('Symfony\Component\Console\Style\SymfonyStyle')
                       ->disableOriginalConstructor()
                       ->getMock();
        $output->expects($this->once())
               ->method('text')
               ->with($this->equalTo('No Metadata Classes to process.'));

        $command->convertDoctrine1Schema(array(), sys_get_temp_dir(), 'annotation', 4, null, $output);
    }
}
