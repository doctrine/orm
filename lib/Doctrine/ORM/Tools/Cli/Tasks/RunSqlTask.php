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

namespace Doctrine\ORM\Tools\Cli\Tasks;

use Doctrine\Common\DoctrineException,
    Doctrine\Common\Util\Debug;

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
    public function extendedHelp()
    {
        $printer = $this->getPrinter();
        
        $printer->write('Task: ')->writeln('run-sql', 'KEYWORD')
                ->write('Synopsis: ');
        $this->_writeSynopsis($printer);
        
        $printer->writeln('Description: Executes arbitrary SQL from a file or directly from the command line.')
                ->writeln('Options:')
                ->write('--sql=<SQL>', 'REQ_ARG')
                ->writeln("\tThe SQL to execute.")
                ->writeln("\t\tIf defined, --file can not be requested on same task")
                ->write(PHP_EOL)
                ->write('--file=<path>', 'REQ_ARG')
                ->writeln("\tThe path to the file with the SQL to execute.")
                ->writeln("\t\tIf defined, --sql can not be requested on same task")
                ->write(PHP_EOL)
                ->write('--depth=<DEPTH>', 'OPT_ARG')
                ->writeln("\tDumping depth of ResultSet graph.");
    }

    /**
     * @inheritdoc
     */
    public function basicHelp()
    {
        $this->_writeSynopsis($this->getPrinter());
    }
    
    private function _writeSynopsis($printer)
    {
        $printer->write('run-sql', 'KEYWORD')
                ->write(' (--file=<path> | --sql=<SQL>)', 'REQ_ARG')
                ->writeln(' --depth=<DEPTH>', 'OPT_ARG');
    }
    
    /**
     * @inheritdoc
     */
    public function validate()
    {
        if ( ! parent::validate()) {
            return false;
        }
        
        $args = $this->getArguments();
        $printer = $this->getPrinter();
        
        $isSql = isset($args['sql']);
        $isFile = isset($args['file']);
        
        if ( ! ($isSql ^ $isFile)) {
            $printer->writeln("One of --sql or --file required, and only one.", 'ERROR');
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Executes the task.
     */
    public function run()
    {
        $args = $this->getArguments();
        
        try {
            if (isset($args['file'])) {
                //TODO
            } else if (isset($args['sql'])) {
                $conn = $this->getEntityManager()->getConnection();
            
                if (preg_match('/^select/i', $args['sql'])) {
                    $stmt = $conn->execute($args['sql']);
                    $resultSet = $stmt->fetchAll(\Doctrine\DBAL\Connection::FETCH_ASSOC);
                } else {
                    $resultSet = $conn->executeUpdate($args['sql']);
                }
            
                $maxDepth = isset($args['depth']) ? $args['depth'] : 7;
        
                Debug::dump($resultSet, $maxDepth);
            }
        } catch (\Exception $ex) {
            throw new DoctrineException($ex);
        }
    }
}