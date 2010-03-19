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
 
namespace Doctrine\Common\Cli;

/**
 * Generic CLI Controller of Tasks execution
 *
 * To include a new Task support, create a task:
 *
 *     [php]
 *     class MyProject\Tools\Cli\Tasks\MyTask extends Doctrine\ORM\Tools\Cli\Tasks\AbstractTask
 *     {
 *         public function run();
 *         public function basicHelp();
 *         public function extendedHelp();
 *         public function validate();
 *     }
 *
 * And then, load the namespace assoaicated an include the support to it in your command-line script:
 *
 *     [php]
 *     $cli = new Doctrine\Common\Cli\CliController();
 *     $cliNS = $cli->getNamespace('custom');
 *     $cliNS->addTask('myTask', 'MyProject\Tools\Cli\Tasks\MyTask');
 *
 * To execute, just type any classify-able name:
 *
 *     $ cli.php custom:my-task
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
class CliController extends AbstractNamespace
{
    /**
     * The CLI processor of tasks
     *
     * @param Configuration $config
     * @param AbstractPrinter $printer CLI Output printer
     */
    public function __construct(Configuration $config, Printers\AbstractPrinter $printer = null)
    {
        $this->setPrinter($printer);
        $this->setConfiguration($config);

        // Include core namespaces of tasks
        $ns = 'Doctrine\Common\Cli\Tasks';
        $this->addNamespace('Core')
             ->addTask('help', $ns . '\HelpTask')
             ->addTask('version', $ns . '\VersionTask');

        $ns = 'Doctrine\ORM\Tools\Cli\Tasks';
        $this->addNamespace('Orm')
             ->addTask('clear-cache', $ns . '\ClearCacheTask')
             ->addTask('convert-mapping', $ns . '\ConvertMappingTask')
             ->addTask('ensure-production-settings', $ns . '\EnsureProductionSettingsTask')
             ->addTask('generate-proxies', $ns . '\GenerateProxiesTask')
             ->addTask('run-dql', $ns . '\RunDqlTask')
             ->addTask('schema-tool', $ns . '\SchemaToolTask')
             ->addTask('version', $ns . '\VersionTask')
             ->addTask('convert-d1-schema', $ns . '\ConvertDoctrine1SchemaTask')
             ->addTask('generate-entities', $ns . '\GenerateEntitiesTask')
             ->addTask('generate-repositories', $ns . '\GenerateRepositoriesTask');

        $ns = 'Doctrine\DBAL\Tools\Cli\Tasks';
        $this->addNamespace('Dbal')
             ->addTask('run-sql', $ns . '\RunSqlTask')
             ->addTask('version', $ns . '\VersionTask');
    }

    /**
     * Add a single task to CLI Core Namespace. This method acts as a delegate.
     * Example of inclusion support to a single task:
     *
     *     [php]
     *     $cli->addTask('my-custom-task', 'MyProject\Cli\Tasks\MyCustomTask');
     *
     * @param string $name CLI Task name
     * @param string $class CLI Task class (FQCN - Fully Qualified Class Name)
     *
     * @return CliController This object instance
     */
    public function addTask($name, $class)
    {
        $this->getNamespace('Core')->addTask($name, $class);

        return $this;
    }

    /**
     * Processor of CLI Tasks. Handles multiple task calls, instantiate
     * respective classes and run them.
     *
     * @param array $args CLI Arguments
     */
    public function run($args = array())
    {
        // Remove script file argument
        $scriptFile = array_shift($args);

        // If not arguments are defined, include "help"
        if (empty($args)) {
            array_unshift($args, 'Core:Help');
        }

        // Process all sent arguments
        $args = $this->_processArguments($args);

        try {
            $this->getPrinter()->writeln('Doctrine Command Line Interface' . PHP_EOL, 'HEADER');

            // Handle possible multiple tasks on a single command
            foreach($args as $taskData) {
                $taskName = $taskData['name'];
                $taskArguments = $taskData['args'];

                $this->runTask($taskName, $taskArguments);
            }

            return true;
        } catch (\Exception $e) {
            $message = $taskName . ' => ' . $e->getMessage();

            if (isset($taskArguments['trace']) && $taskArguments['trace']) {
                $message .= PHP_EOL . PHP_EOL . $e->getTraceAsString();
            }

            $this->getPrinter()->writeln($message, 'ERROR');

            return false;
        }
    }

    /**
     * Executes a given CLI Task
     *
     * @param atring $name CLI Task name
     * @param array $args CLI Arguments
     */
    public function runTask($name, $args = array())
    {
        // Retrieve namespace name, task name and arguments
        $taskPath = explode(':', $name);

        // Find the correct namespace where the task is defined
        $taskName = array_pop($taskPath);
        $taskNamespace = $this->_retrieveTaskNamespace($taskPath);

        $taskNamespace->runTask($taskName, $args);
    }

    /**
     * Processes arguments and returns a structured hierachy.
     * Example:
     *
     * cli.php foo -abc --option=value bar --option -a=value --optArr=value1,value2
     *
     * Returns:
     *
     * array(
     *     0 => array(
     *         'name' => 'foo',
     *         'args' => array(
     *             'a' => true,
     *             'b' => true,
     *             'c' => true,
     *             'option' => 'value',
     *         ),
     *     ),
     *     1 => array(
     *         'name' => 'bar',
     *         'args' => array(
     *             'option' => true,
     *             'a' => 'value',
     *             'optArr' => array(
     *                 'value1', 'value2'
     *             ),
     *         ),
     *     ),
     * )
     *
     * Based on implementation of Patrick Fisher <patrick@pwfisher.com> available at:
     * http://pwfisher.com/nucleus/index.php?itemid=45
     *
     * @param array $args
     *
     * @return array
     */
    private function _processArguments($args = array())
    {
        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
        $regex = '/\s*[,]?\s*"([^"]*)"|\s*[,]?\s*([^,]*)/i';
        $preparedArgs = array();
        $out = & $preparedArgs;

        foreach ($args as $arg){
            // --foo --bar=baz
            if (substr($arg, 0, 2) == '--'){
                $eqPos = strpos($arg, '=');

                // --foo
                if ($eqPos === false){
                    $key = substr($arg, 2);
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                // --bar=baz
                } else {
                    $key = substr($arg, 2, $eqPos - 2);
                    $value = substr($arg, $eqPos + 1);
                    $value = (strpos($value, ' ') !== false) ? $value : array_values(array_filter(
                        explode(',', $value), function ($v) { return trim($v) != ''; }
                    ));
                    $out[$key] = ( ! is_array($value) || empty($value) || (is_array($value) && count($value) > 1))
                        ? $value : $value[0];
                }
            // -k=value -abc
            } else if (substr($arg, 0, 1) == '-'){
                // -k=value
                if (substr($arg, 2, 1) == '='){
                    $key = substr($arg, 1, 1);
                    $value = substr($arg, 3);
                    $value = (strpos($value, ' ') !== false) ? $value : array_values(array_filter(
                        explode(',', $value), function ($v) { return trim($v) != ''; }
                    ));
                    $out[$key] = ( ! is_array($value) || (is_array($value) && count($value) > 1)) 
                        ? $value : $value[0];
                // -abc
                } else {
                    $chars = str_split(substr($arg, 1));

                    foreach ($chars as $char){
                        $key = $char;
                        $out[$key] = isset($out[$key]) ? $out[$key] : true;
                    }
                }
            // plain-arg
            } else {
                $key = count($preparedArgs);
                $preparedArgs[$key] = array(
                    'name' => $arg,
                    'args' => array()
                );
                $out = & $preparedArgs[$key]['args'];
            }
        }

        return $preparedArgs;
    }

    /**
     * Retrieve the correct namespace given a namespace path
     *
     * @param array $namespacePath CLI Namespace path
     *
     * @return AbstractNamespace
     */
    private function _retrieveTaskNamespace($namespacePath)
    {
        $taskNamespace = $this;
        $currentNamespacePath = '';

        // Consider possible missing namespace (ie. "help") and forward to "core"
        if (count($namespacePath) == 0) {
            $namespacePath = array('Core');
        }

        // Loop through each namespace
        foreach ($namespacePath as $namespaceName) {
            $taskNamespace = $taskNamespace->getNamespace($namespaceName);

            // If the given namespace returned "null", throw exception
            if ($taskNamespace === null) {
                throw CliException::namespaceDoesNotExist($namespaceName, $currentNamespacePath);
            }

            $currentNamespacePath = (( ! empty($currentNamespacePath)) ? ':' : '') 
                                  . $taskNamespace->getName();
        }

        return $taskNamespace;
    }
}