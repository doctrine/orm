<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for CreateCommand, DropCommand and UpdateCommand.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractCommand extends Command
{
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param SchemaTool      $schemaTool
     * @param array           $metadatas
     *
     * @return null|int Null or 0 if everything went fine, or an error code.
     */
    abstract protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas);

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emHelper = $this->getHelper('em');

        /* @var $em \Doctrine\ORM\EntityManagerInterface */
        $em = $emHelper->getEntityManager();

        $metadatas = $em->getMetadataFactory()->getAllMetadata();

        if ( ! empty($metadatas)) {
            // Create SchemaTool
            $tool = new SchemaTool($em);

            return $this->executeSchemaCommand($input, $output, $tool, $metadatas);
        } else {
            $output->writeln('No Metadata Classes to process.');

            return 0;
        }
    }
}
