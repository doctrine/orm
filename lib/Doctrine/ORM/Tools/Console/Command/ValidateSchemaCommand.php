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

namespace Doctrine\ORM\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;

/**
 * Validate that the current mapping is valid
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @version     $Revision$
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class ValidateSchemaCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('orm:validate-schema')
        ->setDescription('Validate the mapping files.')
        ->setHelp(<<<EOT
'Validate that the mapping files are correct and in sync with the database.'
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $em = $this->getHelper('em')->getEntityManager();

        $validator = new \Doctrine\ORM\Tools\SchemaValidator($em);
        $errors = $validator->validateMapping();

        $exit = 0;
        if ($errors) {
            foreach ($errors AS $className => $errorMessages) {
                $output->write("<error>[Mapping]  FAIL - The entity-class '" . $className . "' mapping is invalid:</error>\n");
                foreach ($errorMessages AS $errorMessage) {
                    $output->write('* ' . $errorMessage . "\n");
                }
                $output->write("\n");
            }
            $exit += 1;
        } else {
            $output->write('<info>[Mapping]  OK - The mapping files are correct.</info>' . "\n");
        }

        if (!$validator->schemaInSyncWithMetadata()) {
            $output->write('<error>[Database] FAIL - The database schema is not in sync with the current mapping file.</error>' . "\n");
            $exit += 2;
        } else {
            $output->write('<info>[Database] OK - The database schema is in sync with the mapping files.</info>' . "\n");
        }

        exit($exit);
    }
}
