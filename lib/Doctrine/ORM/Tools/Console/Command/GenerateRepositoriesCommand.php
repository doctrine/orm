<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to generate repository classes for mapping information.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GenerateRepositoriesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('orm:generate-repositories')
             ->setAliases(['orm:generate:repositories'])
             ->setDescription('Generate repository classes from your mapping information')
             ->addArgument('dest-path', InputArgument::REQUIRED, 'The path to generate your repository classes.')
             ->addOption('filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A string pattern used to match entities that should be processed.')
             ->setHelp('Generate repository classes from your mapping information.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getHelper('em')->getEntityManager();

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));

        $repositoryName = $em->getConfiguration()->getDefaultRepositoryClassName();

        // Process destination directory
        $destPath = realpath($input->getArgument('dest-path'));

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Entities destination directory '<info>%s</info>' does not exist.", $input->getArgument('dest-path'))
            );
        }

        if ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Entities destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            $numRepositories = 0;
            $generator = new EntityRepositoryGenerator();

            $generator->setDefaultRepositoryName($repositoryName);

            foreach ($metadatas as $metadata) {
                if ($metadata->getCustomRepositoryClassName()) {
                    $output->writeln(
                        sprintf('Processing repository "<info>%s</info>"', $metadata->getCustomRepositoryClassName())
                    );

                    $generator->writeEntityRepositoryClass($metadata->getCustomRepositoryClassName(), $destPath);

                    $numRepositories++;
                }
            }

            if ($numRepositories) {
                // Outputting information message
                $output->writeln(PHP_EOL . sprintf('Repository classes generated to "<info>%s</INFO>"', $destPath));
            } else {
                $output->writeln('No Repository classes were found to be processed.');
            }
        } else {
            $output->writeln('No Metadata Classes to process.');
        }
    }
}
