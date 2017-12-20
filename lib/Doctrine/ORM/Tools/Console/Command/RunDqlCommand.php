<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Util\Debug;

/**
 * Command to execute DQL queries in a given EntityManager.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class RunDqlCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('orm:run-dql')
        ->setDescription('Executes arbitrary DQL directly from the command line.')
        ->setDefinition(
            [
                new InputArgument('dql', InputArgument::REQUIRED, 'The DQL to execute.'),
                new InputOption(
                    'hydrate', null, InputOption::VALUE_REQUIRED,
                    'Hydration mode of result set. Should be either: object, array, scalar or single-scalar.',
                    'object'
                ),
                new InputOption(
                    'first-result', null, InputOption::VALUE_REQUIRED,
                    'The first result in the result set.'
                ),
                new InputOption(
                    'max-result', null, InputOption::VALUE_REQUIRED,
                    'The maximum number of results in the result set.'
                ),
                new InputOption(
                    'depth', null, InputOption::VALUE_REQUIRED,
                    'Dumping depth of Entity graph.', 7
                ),
                new InputOption(
                    'show-sql', null, InputOption::VALUE_NONE,
                    'Dump generated SQL instead of executing query'
                )
            ]
        )
        ->setHelp(<<<'EOT'
Executes arbitrary DQL directly from the command line.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $em \Doctrine\ORM\EntityManagerInterface */
        $em = $this->getHelper('em')->getEntityManager();

        if (($dql = $input->getArgument('dql')) === null) {
            throw new \RuntimeException("Argument 'DQL' is required in order to execute this command correctly.");
        }

        $depth = $input->getOption('depth');

        if ( ! is_numeric($depth)) {
            throw new \LogicException("Option 'depth' must contains an integer value");
        }

        $hydrationModeName = $input->getOption('hydrate');
        $hydrationMode = 'Doctrine\ORM\Query::HYDRATE_' . strtoupper(str_replace('-', '_', $hydrationModeName));

        if ( ! defined($hydrationMode)) {
            throw new \RuntimeException(
                "Hydration mode '$hydrationModeName' does not exist. It should be either: object. array, scalar or single-scalar."
            );
        }

        $query = $em->createQuery($dql);

        if (($firstResult = $input->getOption('first-result')) !== null) {
            if ( ! is_numeric($firstResult)) {
                throw new \LogicException("Option 'first-result' must contains an integer value");
            }

            $query->setFirstResult((int) $firstResult);
        }

        if (($maxResult = $input->getOption('max-result')) !== null) {
            if ( ! is_numeric($maxResult)) {
                throw new \LogicException("Option 'max-result' must contains an integer value");
            }

            $query->setMaxResults((int) $maxResult);
        }

        if ($input->getOption('show-sql')) {
            $output->writeln(Debug::dump($query->getSQL(), 2, true, false));
            return;
        }

        $resultSet = $query->execute([], constant($hydrationMode));

        $output->writeln(Debug::dump($resultSet, $input->getOption('depth'), true, false));
    }
}
