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
    Doctrine\Common\Util\Debug,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup;

/**
 * Task for executing DQL in passed EntityManager.
 * 
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class RunDqlTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $dql = new OptionGroup(OptionGroup::CARDINALITY_1_1, array(
            new Option('dql', '<DQL>', 'The DQL to execute.')
        ));
        
        $depth = new OptionGroup(OptionGroup::CARDINALITY_0_1, array(
            new Option('depth', '<DEPTH>', 'Dumping depth of Entities graph.')
        ));
        
        $doc = $this->getDocumentation();
        $doc->setName('run-dql')
            ->setDescription('Executes arbitrary DQL directly from the command line.')
            ->getOptionGroup()
                ->addOption($dql)
                ->addOption($depth);
    }
    
    /**
     * @inheritdoc
     */
    public function validate()
    {
        $args = $this->getArguments();
        $printer = $this->getPrinter();
        
        if ( ! isset($args['dql'])) {
            $printer->writeln("Argument --dql must be defined.", 'ERROR');
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
            $query = $this->getEntityManager()->createQuery($args['dql']);
            $resultSet = $query->getResult();
        
            $maxDepth = isset($args['depth']) ? $args['depth'] : 7;
        
            Debug::dump($resultSet, $maxDepth);
        } catch (\Exception $ex) {
            throw new DoctrineException($ex);
        }
    }
}