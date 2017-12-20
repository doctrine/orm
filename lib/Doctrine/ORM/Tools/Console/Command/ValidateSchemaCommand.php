<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Tools\SchemaValidator;

/**
 * Command to validate that the current mapping is valid.
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class ValidateSchemaCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('orm:validate-schema')
        ->setDescription('Validate the mapping files.')
        ->addOption(
            'skip-mapping',
            null,
            InputOption::VALUE_NONE,
            'Skip the mapping validation check'
        )
        ->addOption(
            'skip-sync',
            null,
            InputOption::VALUE_NONE,
            'Skip checking if the mapping is in sync with the database'
        )
        ->setHelp(
            <<<'EOT'
'Validate that the mapping files are correct and in sync with the database.'
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getHelper('em')->getEntityManager();
        $validator = new SchemaValidator($em);
        $exit = 0;

        if ($input->getOption('skip-mapping')) {
            $output->writeln('<comment>[Mapping]  Skipped mapping check.</comment>');
        } elseif ($errors = $validator->validateMapping()) {
            foreach ($errors as $className => $errorMessages) {
                $output->writeln("<error>[Mapping]  FAIL - The entity-class '" . $className . "' mapping is invalid:</error>");

                foreach ($errorMessages as $errorMessage) {
                    $output->writeln('* ' . $errorMessage);
                }

                $output->writeln('');
            }

            ++$exit;
        } else {
            $output->writeln('<info>[Mapping]  OK - The mapping files are correct.</info>');
        }

        if ($input->getOption('skip-sync')) {
            $output->writeln('<comment>[Database] SKIPPED - The database was not checked for synchronicity.</comment>');
        } elseif (!$validator->schemaInSyncWithMetadata()) {
            $output->writeln('<error>[Database] FAIL - The database schema is not in sync with the current mapping file.</error>');
            $exit += 2;
        } else {
            $output->writeln('<info>[Database] OK - The database schema is in sync with the mapping files.</info>');
        }

        return $exit;
    }
}
