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
 
namespace Doctrine\ORM\Tools;

use Doctrine\Common\Util\Inflector,
    Doctrine\ORM\Tools\Cli\Printer;

/**
 * Generic CLI Runner of Tasks
 *
 * To include a new Task support, create a task:
 *
 *     [php]
 *     class MyProject\Tools\Cli\Tasks\MyTask extends Doctrine\ORM\Tools\Cli\Task {
 *         public function run();
 *         public function basicHelp();
 *         public function extendedHelp();
 *         public function validate();
 *     }
 *
 * And then, include the support to it in your command-line script:
 *
 *     [php]
 *     $cli = new Doctrine\ORM\Tools\Cli();
 *     $cli->addTask('myTask', 'MyProject\Tools\Cli\Tasks\MyTask');
 *
 * To execute, just type any classify-able name:
 *
 *     [bash]
 *     cli.php my-task
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Cli
{
    /**
     * @var Cli\AbstractPrinter CLI Printer instance
     */
    private $_printer = null;
    
    /**
     * @var array Available tasks
     */
    private $_tasks = array();
    

    public function __construct($printer = null)
    {
        //$this->_printer = new Printer\Normal();
        $this->_printer = $printer ?: new Printer\AnsiColor();
        
        // Include core tasks
        $ns = 'Doctrine\ORM\Tools\Cli\Task';
        
        $this->addTasks(array(
            'help'    => $ns . '\Help',
            'version' => $ns . '\Version',
        ));
    }
    
    public function addTasks($tasks)
    {
        foreach ($tasks as $name => $class) {
            $this->addTask($name, $class);
        }
    }
    
    public function addTask($name, $class)
    {
        // Convert $name into a class equivalent 
        // (ie. 'show_version' => 'showVersion')
        $name = $this->_processTaskName($name);
        
        $this->_tasks[$name] = $class;
    }
    
    public function run($args = array())
    {
        // Remove script file argument
        $scriptFile = array_shift($args);
        
        // Automatically prepend 'help' task if:
        // 1- No arguments were passed
        // 2- First item is not a valid task name
        if (empty($args) || (isset($args[0]) && strpos($args[0], '-') !== false)) {
            array_unshift($args, 'help');
        }
        
        // Process all sent arguments
        $processedArgs = $this->_processArguments($args); 
        
        try {
            // Handle possible multiple tasks on a single command
            foreach($processedArgs as $taskData) {
                // Retrieve the task name and arguments
                $taskName = $this->_processTaskName($taskData['name']);
                $taskArguments = $taskData['args'];
                
                // Always include supported Tasks as argument
                $taskArguments['availableTasks'] = $this->_tasks;
        
                // Check if task exists
                if (isset($this->_tasks[$taskName]) && class_exists($this->_tasks[$taskName], true)) {
                    // Instantiate and execute the task
                    $task = new $this->_tasks[$taskName]();
                    $task->setPrinter($this->_printer);
                    $task->setArguments($taskArguments);
                
                    if (isset($taskArguments['help']) && $taskArguments['help']) {
                        $task->extendedHelp(); // User explicitly asked for task help
                    } else if ($this->_isTaskValid($task)) {
                        $task->run();
                    } else {
                        $task->basicHelp(); // Fallback of not-valid task arguments
                    }
                } else {
                    throw \Doctrine\Common\DoctrineException::updateMe(
                        'Unexistent task or attached task class does not exist.'
                    );
                }
            }
        } catch (\Doctrine\Common\DoctrineException $e) {
            $this->_printer->write(
                $taskName . ':' . $e->getMessage() . PHP_EOL, 'ERROR'
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
                    $value = explode(',', substr($arg, $eqPos + 1));
                    $out[$key] = (count($value) > 1) ? $value : $value[0];
                }
            // -k=value -abc
            } else if (substr($arg, 0, 1) == '-'){
                // -k=value
                if (substr($arg, 2, 1) == '='){
                    $key = substr($arg, 1, 1);
                    $value = explode(',', substr($arg, 3));
                    $out[$key] = (count($value) > 1) ? $value : $value[0];
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
    
    private function _isTaskValid($task)
    {
        // TODO: Should we check for required and optional arguments here?
        return $task->validate();
    }
}