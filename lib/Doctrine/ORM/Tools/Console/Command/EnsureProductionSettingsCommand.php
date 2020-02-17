<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Command to ensure that Doctrine is properly configured for a production environment.
 */
class EnsureProductionSettingsCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('orm:ensure-production-settings')
             ->setDescription('Verify that Doctrine is properly configured for a production environment')
             ->addOption('complete', null, InputOption::VALUE_NONE, 'Flag to also inspect database connection existence.')
             ->setHelp('Verify that Doctrine is properly configured for a production environment.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);

        $em = $this->getHelper('em')->getEntityManager();

        try {
            $em->getConfiguration()->ensureProductionSettings();

            if ($input->getOption('complete') !== null) {
                $em->getConnection()->connect();
            }
        } catch (Throwable $e) {
            $ui->error($e->getMessage());

            return 1;
        }

        $ui->success('Environment is correctly configured for production.');

        return 0;
    }
}
