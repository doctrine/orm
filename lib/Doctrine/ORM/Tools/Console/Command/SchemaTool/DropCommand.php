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
 * Command to drop the database schema for a set of classes based on their mappings.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class DropCommand extends AbstractCommand
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('orm:schema-tool:drop')
        ->setDescription(
            'Drop the complete database schema of EntityManager Storage Connection or generate the corresponding SQL output.'
        )
        ->setDefinition(array(
            new InputOption(
                'dump-sql', null, InputOption::PARAMETER_NONE,
                'Instead of try to apply generated SQLs into EntityManager Storage Connection, output them.'
            ),
            new InputOption(
                'force', null, InputOption::PARAMETER_NONE,
                "Don't ask for the deletion of the database, but force the operation to run."
            ),
        ))
        ->setHelp(<<<EOT
Processes the schema and either drop the database schema of EntityManager Storage Connection or generate the SQL output.
Beware that the complete database is dropped by this command, even tables that are not relevant to your metadata model.
EOT
        );
    }

    protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas)
    {
        if ($input->getOption('dump-sql') === true) {
            $sqls = $schemaTool->getDropSchemaSql($metadatas);
            $output->write(implode(';' . PHP_EOL, $sqls) . PHP_EOL);
        } else if ($input->getOption('force') === true) {
            $output->write('Dropping database schema...' . PHP_EOL);
            $schemaTool->dropSchema($metadatas);
            $output->write('Database schema dropped successfully!' . PHP_EOL);
        } else {
            $sqls = $schemaTool->getDropSchemaSql($metadatas);

            if (count($sqls)) {
                $output->write('Schema-Tool would execute ' . count($sqls) . ' queries to drop the database.' . PHP_EOL);
                $output->write('Please run the operation with --force to execute these queries or use --dump-sql to see them.' . PHP_EOL);
            } else {
                $output->write('Nothing to drop. The database is empty!' . PHP_EOL);
            }
        }
    }
}
