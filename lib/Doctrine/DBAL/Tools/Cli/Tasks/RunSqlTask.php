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

namespace Doctrine\DBAL\Tools\Cli\Tasks;

use Doctrine\Common\Cli\Tasks\AbstractTask,
    Doctrine\Common\Cli\CliException,
    Doctrine\Common\Util\Debug,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup;

/**
 * Task for executing arbitrary SQL that can come from a file or directly from
 * the command line.
 * 
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class RunSqlTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $dqlAndFile = new OptionGroup(OptionGroup::CARDINALITY_1_1, array(
            new Option(
                'sql', '<SQL>', 'The SQL to execute.' . PHP_EOL . 
                'If defined, --file can not be requested on same task.'
            ),
            new Option(
                'file', '<PATH>', 'The path to the file with the SQL to execute.' . PHP_EOL . 
                'If defined, --sql can not be requested on same task.'
            )
        ));
        
        $depth = new OptionGroup(OptionGroup::CARDINALITY_0_1, array(
            new Option('depth', '<DEPTH>', 'Dumping depth of Entities graph.')
        ));
        
        $doc = $this->getDocumentation();
        $doc->setName('run-sql')
            ->setDescription('Executes arbitrary SQL from a file or directly from the command line.')
            ->getOptionGroup()
                ->addOption($dqlAndFile)
                ->addOption($depth);
    }
    
    /**
     * @inheritdoc
     */
    public function validate()
    {
        $arguments = $this->getArguments();
        $em = $this->getConfiguration()->getAttribute('em');
        
        if ($em === null) {
            throw new CliException(
                "Attribute 'em' of CLI Configuration is not defined or it is not a valid EntityManager."
            );
        }
        
        if ( ! (isset($arguments['sql']) ^ isset($arguments['file']))) {
            throw new CliException('One of --sql or --file required, and only one.');
        }
        
        return true;
    }
    
    
    /**
     * Executes the task.
     */
    public function run()
    {
        $arguments = $this->getArguments();
        
        if (isset($arguments['file'])) {
            //TODO
        } else if (isset($arguments['sql'])) {
            $em = $this->getConfiguration()->getAttribute('em');
            
            if (preg_match('/^select/i', $arguments['sql'])) {
                $stmt = $em->getConnection()->execute($arguments['sql']);
                $resultSet = $stmt->fetchAll(\Doctrine\DBAL\Connection::FETCH_ASSOC);
            } else {
                $resultSet = $em->getConnection()->executeUpdate($arguments['sql']);
            }
            
            $maxDepth = isset($arguments['depth']) ? $arguments['depth'] : 7;
        
            Debug::dump($resultSet, $maxDepth);
        }
    }
}