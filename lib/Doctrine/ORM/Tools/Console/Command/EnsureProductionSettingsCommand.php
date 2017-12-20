<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command to ensure that Doctrine is properly configured for a production environment.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EnsureProductionSettingsCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('orm:ensure-production-settings')
        ->setDescription('Verify that Doctrine is properly configured for a production environment.')
        ->setDefinition(
            [
                new InputOption(
                    'complete', null, InputOption::VALUE_NONE,
                    'Flag to also inspect database connection existence.'
                )
            ]
        )
        ->setHelp(<<<'EOT'
Verify that Doctrine is properly configured for a production environment.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getHelper('em')->getEntityManager();

        try {
            $em->getConfiguration()->ensureProductionSettings();

            if ($input->getOption('complete') !== null) {
                $em->getConnection()->connect();
            }
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $output->writeln('<info>Environment is correctly configured for production.</info>');
    }
}
