<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

/**
 * Command to create the database schema for a set of classes based on their mappings.
 *
 * @link    www.doctrine-project.org
 */
class CreateCommand extends AbstractCommand
{
    /** @return void */
    protected function configure()
    {
        $this->setName('orm:schema-tool:create')
             ->setDescription('Processes the schema and either create it directly on EntityManager Storage Connection or generate the SQL output')
             ->addOption('em', null, InputOption::VALUE_REQUIRED, 'Name of the entity manager to operate on')
             ->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Instead of trying to apply generated SQLs into EntityManager Storage Connection, output them.')
             ->setHelp(<<<'EOT'
Processes the schema and either create it directly on EntityManager Storage Connection or generate the SQL output.

<comment>Hint:</comment> If you have a database with tables that should not be managed
by the ORM, you can use a DBAL functionality to filter the tables and sequences down
on a global level:

    $config->setFilterSchemaAssetsExpression($regexp);
EOT
             );
    }

    /**
     * {@inheritDoc}
     */
    protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas, SymfonyStyle $ui)
    {
        $dumpSql = $input->getOption('dump-sql') === true;

        if ($dumpSql) {
            $sqls = $schemaTool->getCreateSchemaSql($metadatas);

            foreach ($sqls as $sql) {
                $ui->writeln(sprintf('%s;', $sql));
            }

            return 0;
        }

        $notificationUi = $ui->getErrorStyle();

        $notificationUi->caution('This operation should not be executed in a production environment!');

        $notificationUi->text('Creating database schema...');
        $notificationUi->newLine();

        $schemaTool->createSchema($metadatas);

        $notificationUi->success('Database schema created successfully!');

        return 0;
    }
}
