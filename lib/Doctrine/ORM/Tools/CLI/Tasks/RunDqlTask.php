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

namespace Doctrine\ORM\Tools\CLI\Tasks;

use Doctrine\Common\CLI\Tasks\AbstractTask,
    Doctrine\Common\CLI\CLIException,
    Doctrine\Common\Util\Debug,
    Doctrine\Common\CLI\Option,
    Doctrine\Common\CLI\OptionGroup,
    Doctrine\ORM\Query;

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
        
        $hydrate = new OptionGroup(OptionGroup::CARDINALITY_0_1, array(
            new Option(
                'hydrate', '<HYDRATION_MODE>', 
                'Hydration mode of result set.' . PHP_EOL . 
                'Should be either: object, array, scalar or single-scalar.'
            )
        ));
 
        $firstResult = new OptionGroup(OptionGroup::CARDINALITY_0_1, array(
            new Option('first-result', '<INTEGER>', 'The first result in the result set.')
        ));
        
        $maxResults = new OptionGroup(OptionGroup::CARDINALITY_0_1, array(
            new Option('max-results', '<INTEGER>', 'The maximum number of results in the result set.')
        ));
        
        $depth = new OptionGroup(OptionGroup::CARDINALITY_0_1, array(
            new Option('depth', '<DEPTH>', 'Dumping depth of Entities graph.')
        ));
        
        $doc = $this->getDocumentation();
        $doc->setName('run-dql')
            ->setDescription('Executes arbitrary DQL directly from the command line.')
            ->getOptionGroup()
                ->addOption($dql)
                ->addOption($hydrate)
                ->addOption($firstResult)
                ->addOption($maxResults)
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
            throw new CLIException(
                "Attribute 'em' of CLI Configuration is not defined or it is not a valid EntityManager."
            );
        }
        
        if ( ! isset($arguments['dql'])) {
            throw new CLIException('Argument --dql must be defined.');
        }

        if (isset($arguments['hydrate'])) {
            $hydrationModeName = 'Doctrine\ORM\Query::HYDRATE_' . strtoupper(str_replace('-', '_', $arguments['hydrate']));

            if ( ! defined($hydrationModeName)) {
                throw new CLIException("Argument --hydrate must be either 'object', 'array', 'scalar' or 'single-scalar'.");
            }
        }

        if (isset($arguments['first-result']) && ! ctype_digit($arguments['first-result'])) {
            throw new CLIException('Argument --first-result must be an integer value.');
        }

        if (isset($arguments['max-results']) && ! ctype_digit($arguments['max-results'])) {
            throw new CLIException('Argument --max-results must be an integer value.');
        }
        
        return true;
    }
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        $arguments = $this->getArguments();
        $em = $this->getConfiguration()->getAttribute('em');
        $query = $em->createQuery($arguments['dql']);

        $hydrationMode = isset($arguments['hydrate'])
            ? constant('Doctrine\ORM\Query::HYDRATE_' . strtoupper(str_replace('-', '_', $arguments['hydrate']))) 
            : Query::HYDRATE_OBJECT;

        if (isset($arguments['first-result'])) {
            $query->setFirstResult($arguments['first-result']);
        }

        if (isset($arguments['max-results'])) {
            $query->setMaxResults($arguments['max-results']);
        }

        $resultSet = $query->getResult($hydrationMode);
        $maxDepth = isset($arguments['depth']) ? $arguments['depth'] : 7; 
       
        Debug::dump($resultSet, $maxDepth);
    }
}