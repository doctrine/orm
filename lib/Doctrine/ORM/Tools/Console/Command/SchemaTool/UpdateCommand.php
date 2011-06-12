<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Console\Command\SchemaTool;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Doctrine\ORM\Tools\SchemaTool;

/**
 * Command to generate the SQL needed to update the database schema to match
 * the current mapping information.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Ryan Weaver <ryan@thatsquality.com>
 */
class UpdateCommand extends AbstractCommand
{
    protected $name = 'orm:schema-tool:update';

    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName($this->name)
        ->setDescription(
            'Executes (or dumps) the SQL needed to update the database schema to match the current mapping metadata.'
        )
        ->setDefinition(array(
            new InputOption(
                'complete', null, InputOption::VALUE_NONE,
                'If defined, all assets of the database which are not relevant to the current metadata will be dropped.'
            ),

            new InputOption(
                'dump-sql', null, InputOption::VALUE_NONE,
                'Dumps the generated SQL statements to the screen (does not execute them).'
            ),
            new InputOption(
                'force', null, InputOption::VALUE_NONE,
                'Causes the generated SQL statements to be physically executed against your database.'
            ),
        ));

        $fullName = $this->getName();
        $this->setHelp(<<<EOT
The <info>$fullName</info> command generates the SQL needed to
synchronize the database schema with the current mapping metadata of the
default entity manager.

For example, if you add metadata for a new column to an entity, this command
would generate and output the SQL needed to add the new column to the database:

<info>$fullName --dump-sql</info>

Alternatively, you can execute the generated queries:

<info>$fullName --force</info>

Finally, be aware that if the <info>--complete</info> option is passed, this
task will drop all database assets (e.g. tables, etc) that are *not* described
by the current metadata. In other words, without this option, this task leaves
untouched any "extra" tables that exist in the database, but which aren't
described by any metadata.
EOT
        );
    }

    protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas)
    {
        // Defining if update is complete or not (--complete not defined means $saveMode = true)
        $saveMode = ($input->getOption('complete') !== true);

        $sqls = $schemaTool->getUpdateSchemaSql($metadatas, $saveMode);
        if (0 == count($sqls)) {
            $output->writeln('Nothing to update - your database is already in sync with the current entity metadata.');

            return;
        }

        $dumpSql = (true === $input->getOption('dump-sql'));
        $force = (true === $input->getOption('force'));
        if ($dumpSql && $force) {
            throw new \InvalidArgumentException('You can pass either the --dump-sql or the --force option (but not both simultaneously).');
        }

        if ($dumpSql) {
            $output->writeln(implode(';' . PHP_EOL, $sqls));
        } else if ($force) {
            $output->writeln('Updating database schema...');
            $schemaTool->updateSchema($metadatas, $saveMode);
            $output->writeln(sprintf('Database schema updated successfully! "<info>%s</info>" queries were executed', count($sqls)));
        } else {
            $output->writeln('<comment>ATTENTION</comment>: This operation should not be executed in a production environment.');
            $output->writeln('           Use the incremental update to detect changes during development and use');
            $output->writeln('           the SQL DDL provided to manually update your database in production.');
            $output->writeln('');

            $output->writeln(sprintf('The Schema-Tool would execute <info>"%s"</info> queries to update the database.', count($sqls)));
            $output->writeln('Please run the operation by passing one of the following options:');
            
            $output->writeln(sprintf('    <info>%s --force</info> to execute the command', $this->getFullName()));
            $output->writeln(sprintf('    <info>%s --dump-sql</info> to dump the SQL statements to the screen', $this->getFullName()));
        }
    }
}
