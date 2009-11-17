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
 
namespace Doctrine\ORM\Tools\Cli;

use Doctrine\Common\Util\Inflector,
    Doctrine\Common\Cli\Printers\AbstractPrinter,
    Doctrine\Common\Cli\Printers\AnsiColorPrinter,
    Doctrine\ORM\Tools\Cli\Tasks\AbstractTask;

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
 * And then, include the support to it in your command-line script:
 *
 *     [php]
 *     $cli = new Doctrine\ORM\Tools\Cli\CliController();
 *     $cli->addTask('myTask', 'MyProject\Tools\Cli\Tasks\MyTask');
 *
 * To execute, just type any classify-able name:
 *
 *     $ cli.php my-task
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class CliController
{
    /**
     * @var AbstractPrinter CLI Printer instance
     */
    private $_printer = null;
    
    /**
     * @var array Available tasks
     */
    private $_tasks = array();
    
    /**
     * The CLI processor of tasks
     *
     * @param AbstractPrinter $printer CLI Output printer
     */
    public function __construct(AbstractPrinter $printer = null)
    {
        //$this->_printer = new Printer\Normal();
        $this->_printer = $printer ?: new AnsiColorPrinter;
        
        // Include core tasks
        $ns = 'Doctrine\ORM\Tools\Cli\Tasks';
        
        $this->addTasks(array(
            'help'                       => $ns . '\HelpTask',
            'version'                    => $ns . '\VersionTask',
            'schema-tool'                => $ns . '\SchemaToolTask',
            'run-sql'                    => $ns . '\RunSqlTask',
            'run-dql'                    => $ns . '\RunDqlTask',
            'convert-mapping'            => $ns . '\ConvertMappingTask',
            'generate-proxies'           => $ns . '\GenerateProxiesTask',
            'clear-cache'                => $ns . '\ClearCacheTask',
            'ensure-production-settings' => $ns . '\EnsureProductionSettingsTask'
        ));
    }
    
    /**
     * Add a collection of tasks to the CLI.
     * To include them, just call the method with the following structure:
     *
     *    [php]
     *    $cli->addTasks(array(
     *        'my-custom-task' => 'MyProject\Cli\Tasks\MyCustomTask',
     *        ...
     *    ));
     *
     * @param array $tasks CLI Tasks to be included
     * @return CliController This object instance
     */
    public function addTasks($tasks)
    {
        foreach ($tasks as $name => $class) {
            $this->addTask($name, $class);
        }
        
        return $this;
    }
    
    /**
     * Add a single task to CLI.
     * Example of inclusion support to a single task:
     *
     *     [php]
     *     $cli->addTask('my-custom-task', 'MyProject\Cli\Tasks\MyCustomTask');
     *
     * @param string $name CLI Task name
     * @param string $class CLI Task class (FQCN - Fully Qualified Class Name)
     * @return CliController This object instance
     */
    public function addTask($name, $class)
    {
        // Convert $name into a class equivalent 
        // (ie. 'show_version' => 'showVersion')
        $name = $this->_processTaskName($name);
        
        $this->_tasks[$name] = $class;
        
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
        
        // Automatically prepend 'help' task if:
        // 1- No arguments were passed
        // 2- First item is not a valid task name
        if (empty($args) || ! isset($this->_tasks[$this->_processTaskName($args[0])])) {
            array_unshift($args, 'help');
        }
        
        // Process all sent arguments
        $processedArgs = $this->_processArguments($args);
        
        try {
            $this->_printer->writeln('Doctrine Command Line Interface' . PHP_EOL, 'HEADER');
            
            // Handle possible multiple tasks on a single command
            foreach($processedArgs as $taskData) {
                // Retrieve the task name and arguments
                $taskName = $this->_processTaskName($taskData['name']);
                $taskArguments = $taskData['args'];
                
                // Check if task exists
                if (isset($this->_tasks[$taskName]) && class_exists($this->_tasks[$taskName], true)) {
                    // Initializing EntityManager
                    $em = $this->_initializeEntityManager($processedArgs, $taskArguments);
                
                    // Instantiate and execute the task
                    $task = new $this->_tasks[$taskName]($this->_printer);
                    $task->setAvailableTasks($this->_tasks);
                    $task->setEntityManager($em);
                    $task->setArguments($taskArguments);
                
                    if (
                        (isset($taskArguments['help']) && $taskArguments['help']) ||
                        (isset($taskArguments['h']) && $taskArguments['h'])
                    ) {
                        $task->extendedHelp(); // User explicitly asked for help option
                    } else if ($this->_isTaskValid($task)) {
                        $task->run();
                    } else {
                        $this->_printer->write(PHP_EOL);
                        $task->basicHelp(); // Fallback of not-valid task arguments
                        $this->_printer->write(PHP_EOL);
                    }
                } else {
                    throw \Doctrine\Common\DoctrineException::taskDoesNotExist($taskName);
                }
            }
        } catch (\Doctrine\Common\DoctrineException $e) {
            $this->_printer->writeln(
                $taskName . ': ' . $e->getMessage() . PHP_EOL . PHP_EOL . $e->getTraceAsString(), 'ERROR'
            );
        }
    }
    
    /**
     * Processes the given task name and return it formatted
     *
     * @param string $taskName Task name
     * @return string
     */
    private function _processTaskName($taskName)
    {
        $taskName = str_replace('-', '_', $taskName);
        
        return Inflector::classify($taskName);
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
     *         'option' => true,
     *         'a' => 'value',
     *         'optArr' => array(
     *             'value1', 'value2'
     *         ),
     *     ),
     * )
     *
     * Based on implementation of Patrick Fisher <patrick@pwfisher.com> available at:
     * 
     * http://pwfisher.com/nucleus/index.php?itemid=45
     *
     * @param array $args
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
                    $value = (strpos($value, ' ') !== false) ? $value 
                        : array_values(array_filter(explode(',', $value), function ($v) { return trim($v) != ''; }));
                    $out[$key] = ( ! is_array($value) || (is_array($value) && count($value) > 1)) 
                        ? $value : $value[0];
                }
            // -k=value -abc
            } else if (substr($arg, 0, 1) == '-'){
                // -k=value
                if (substr($arg, 2, 1) == '='){
                    $key = substr($arg, 1, 1);
                    $value = substr($arg, 3);
                    $value = (strpos($value, ' ') !== false) ? $value 
                        : array_values(array_filter(explode(',', $value), function ($v) { return trim($v) != ''; }));
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
     * Checks if CLI Task is valid based on given arguments.
     *
     * @param AbstractTask $task CLI Task instance
     */
    private function _isTaskValid(AbstractTask $task)
    {
        // TODO: Should we change the behavior and check for 
        // required and optional arguments here?
        return $task->validate();
    }
    
    /**
     * Initialized Entity Manager for Tasks
     *
     * @param array CLI Task arguments
     * @return EntityManager
     */
    private function _initializeEntityManager(array $args, array &$taskArgs)
    {
        // Initialize EntityManager
        $configFile = ( ! isset($taskArgs['config'])) ? './cli-config.php' : $taskArgs['config'];

        if (file_exists($configFile)) {
            // Including configuration file
            require $configFile;
            
            // Check existance of EntityManager
            if ( ! isset($em)) {
                throw new \Doctrine\Common\DoctrineException(
                    'No EntityManager created in configuration'
                );
            }
            
            // Check for gloal argument options here
            if (isset($globalArguments)) {
                // Merge arguments. Values specified via the CLI take preference.
                $taskArgs = array_merge($globalArguments, $taskArgs);
            }
            
            return $em;
        } else {
            throw new \Doctrine\Common\DoctrineException(
                'Requested configuration file [' . $configFile . '] does not exist'
            );
        }
        
        return null;
    }
}