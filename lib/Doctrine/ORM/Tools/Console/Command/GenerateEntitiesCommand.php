<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Command to generate entity classes and method stubs from your mapping information.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GenerateEntitiesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('orm:generate-entities')
        ->setAliases(['orm:generate:entities'])
        ->setDescription('Generate entity classes and method stubs from your mapping information.')
        ->setDefinition(
            [
                new InputOption(
                    'filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                    'A string pattern used to match entities that should be processed.'
                ),
                new InputArgument(
                    'dest-path', InputArgument::REQUIRED, 'The path to generate your entity classes.'
                ),
                new InputOption(
                    'generate-annotations', null, InputOption::VALUE_OPTIONAL,
                    'Flag to define if generator should generate annotation metadata on entities.', false
                ),
                new InputOption(
                    'generate-methods', null, InputOption::VALUE_OPTIONAL,
                    'Flag to define if generator should generate stub methods on entities.', true
                ),
                new InputOption(
                    'regenerate-entities', null, InputOption::VALUE_OPTIONAL,
                    'Flag to define if generator should regenerate entity if it exists.', false
                ),
                new InputOption(
                    'update-entities', null, InputOption::VALUE_OPTIONAL,
                    'Flag to define if generator should only update entity if it exists.', true
                ),
                new InputOption(
                    'extend', null, InputOption::VALUE_REQUIRED,
                    'Defines a base class to be extended by generated entity classes.'
                ),
                new InputOption(
                    'num-spaces', null, InputOption::VALUE_REQUIRED,
                    'Defines the number of indentation spaces', 4
                ),
                new InputOption(
                    'no-backup', null, InputOption::VALUE_NONE,
                    'Flag to define if generator should avoid backuping existing entity file if it exists.'
                )
            ]
        )
        ->setHelp(<<<EOT
Generate entity classes and method stubs from your mapping information.

If you use the <comment>--update-entities</comment> or <comment>--regenerate-entities</comment> flags your existing
code gets overwritten. The EntityGenerator will only append new code to your
file and will not delete the old code. However this approach may still be prone
to error and we suggest you use code repositories such as GIT or SVN to make
backups of your code.

It makes sense to generate the entity code if you are using entities as Data
Access Objects only and don't put much additional logic on them. If you are
however putting much more logic on the entities you should refrain from using
the entity-generator and code your entities manually.

<error>Important:</error> Even if you specified Inheritance options in your
XML Mapping files the generator cannot generate the base and child classes
for you correctly, because it doesn't know which class is supposed to extend
which. You have to adjust the entity code manually for inheritance to work!
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getHelper('em')->getEntityManager();

        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $metadatas = $cmf->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));

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
            // Create EntityGenerator
            $entityGenerator = new EntityGenerator();

            $entityGenerator->setGenerateAnnotations($input->getOption('generate-annotations'));
            $entityGenerator->setGenerateStubMethods($input->getOption('generate-methods'));
            $entityGenerator->setRegenerateEntityIfExists($input->getOption('regenerate-entities'));
            $entityGenerator->setUpdateEntityIfExists($input->getOption('update-entities'));
            $entityGenerator->setNumSpaces($input->getOption('num-spaces'));
            $entityGenerator->setBackupExisting(!$input->getOption('no-backup'));

            if (($extend = $input->getOption('extend')) !== null) {
                $entityGenerator->setClassToExtend($extend);
            }

            foreach ($metadatas as $metadata) {
                $output->writeln(
                    sprintf('Processing entity "<info>%s</info>"', $metadata->getClassName())
                );
            }

            // Generating Entities
            $entityGenerator->generate($metadatas, $destPath);

            // Outputting information message
            $output->writeln(PHP_EOL . sprintf('Entity classes generated to "<info>%s</INFO>"', $destPath));
        } else {
            $output->writeln('No Metadata Classes to process.');
        }
    }
}
