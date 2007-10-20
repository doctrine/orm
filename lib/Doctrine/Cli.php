<?php
/*
 *  $Id: Cli.php 2761 2007-10-07 23:42:29Z zYne $
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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Cli
 *
 * @package     Doctrine
 * @subpackage  Cli
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2761 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Cli
{
    protected $tasks        = array(),
              $taskInstance = null,
              $formatter    = null,
              $scriptName   = null,
              $message      = null,
              $config       = array();
    
    /**
     * __construct
     *
     * @param string $config 
     * @return void
     */
    public function __construct($config = array())
    {
        $this->config = $config;
        $this->formatter = new Doctrine_Cli_AnsiColorFormatter();
    }
    
    /**
     * notify
     *
     * @param string $notification 
     * @return void
     */
    public function notify($notification = null)
    {
        echo $this->formatter->format($this->taskInstance->getTaskName(), 'INFO') . ' - ' . $this->formatter->format($notification, 'HEADER') . "\n";
    }
    
    /**
     * notifyException
     *
     * @param string $exception 
     * @return void
     */
    public function notifyException($exception)
    {
        echo $this->formatter->format($exception->getMessage(), 'ERROR') . "\n";
    }

    /**
     * run
     *
     * @param string $args 
     * @return void
     * @throws new Doctrine_Cli_Exception
     */
    public function run($args)
    {
        echo "\n";
        
        try {
            $this->_run($args);
        } catch (Exception $exception) {
            $this->notifyException($exception);
        }
        
        echo "\n";
    }
    
    protected function _getTaskClassFromArgs($args)
    {
        $taskName = str_replace('-', '_', $args[1]);
        $taskClass = 'Doctrine_Task_' . Doctrine::classify($taskName);
        
        return $taskClass;
    }
    
    protected function _run($args)
    {        
        $this->scriptName = $args[0];
        $taskName = $args[1];
        
        $arg1 = isset($args[1]) ? $args[1]:null;
        
        if (!$arg1 || $arg1 == 'help') {
            echo $this->printTasks(null, $arg1 == 'help' ? true:false);
            return;
        }
        
        if (isset($args[1]) && isset($args[2]) && $args[2] == 'help') {
            echo $this->printTasks($args[1], true);
            return;
        }
        
        $taskClass = $this->_getTaskClassFromArgs($args);
        
        if (!class_exists($taskClass)) {
            throw new Doctrine_Cli_Exception('Cli task could not be found: ' . $taskClass);
        }
        
        unset($args[0]);
        unset($args[1]);
        
        $this->taskInstance = new $taskClass($this);
        
        $args = $this->prepareArgs($args);
        
        $this->taskInstance->setArguments($args);
        
        try {
            if ($this->taskInstance->validate()) {
                $this->taskInstance->execute();
            } else {
                echo $this->formatter->format('Requires arguments missing!!', 'ERROR') . "\n\n";
                echo $this->printTasks($taskName, true);
            }
        } catch (Exception $e) {
            throw new Doctrine_Cli_Exception($e->getMessage());
        }
    }

    /**
     * prepareArgs
     *
     * @param string $args 
     * @return array $prepared
     */
    protected function prepareArgs($args)
    {
        $taskInstance = $this->taskInstance;
        
        $args = array_values($args);
        
        // First lets load populate an array with all the possible arguments. required and optional
        $prepared = array();
        
        $requiredArguments = $taskInstance->getRequiredArguments();
        foreach ($requiredArguments as $key => $arg) {
            $prepared[$arg] = null;
        }
        
        $optionalArguments = $taskInstance->getOptionalArguments();
        foreach ($optionalArguments as $key => $arg) {
            $prepared[$arg] = null;
        }
        
        // If we have a config array then lets try and fill some of the arguments with the config values
        if (is_array($this->config) && !empty($this->config)) {
            foreach ($this->config as $key => $value) {
                if (array_key_exists($key, $prepared)) {
                    $prepared[$key] = $value;
                }
            }
        }
        
        // Now lets fill in the entered arguments to the prepared array
        $copy = $args;
        foreach ($prepared as $key => $value) {
            if (!$value && !empty($copy)) {
                $prepared[$key] = $copy[0];
                unset($copy[0]);
                $copy = array_values($copy);
            }
        }
        
        return $prepared;
    }

    /**
     * printTasks
     *
     * Prints an index of all the available tasks in the CLI instance
     * 
     * @return void
     */
    public function printTasks($task = null, $full = false)
    {
        $task = Doctrine::classify(str_replace('-', '_', $task));
        
        $tasks = $this->loadTasks();
        
        echo $this->formatter->format("Doctrine Command Line Interface", 'HEADER') . "\n\n";
        
        foreach ($tasks as $taskName)
        {
            if ($task != null && strtolower($task) != strtolower($taskName)) {
                continue;
            }
            
            $className = 'Doctrine_Task_' . $taskName;
            $taskInstance = new $className();
            $taskInstance->taskName = str_replace('_', '-', Doctrine::tableize($taskName));         
            
            $syntax = $this->scriptName . ' ' . $taskInstance->getTaskName();
            
            echo $this->formatter->format($syntax, 'INFO'); 
            
            if ($full) {
                echo " - " . $taskInstance->getDescription() . "\n";  
                
                $args = null;
                
                $requiredArguments = $taskInstance->getRequiredArgumentsDescriptions();
                
                if (!empty($requiredArguments)) {
                    foreach ($requiredArguments as $name => $description) {
                        $args .= $this->formatter->format($name, "ERROR");
                        
                        if (isset($this->config[$name])) {
                            $args .= " - " . $this->formatter->format($this->config[$name], 'COMMENT');
                        } else {
                            $args .= " - " . $description;
                        }
                        
                        $args .= "\n";
                    }
                }
            
                $optionalArguments = $taskInstance->getOptionalArgumentsDescriptions();
                
                if (!empty($optionalArguments)) {
                    foreach ($optionalArguments as $name => $description) {
                        $args .= $name . ' - ' . $description."\n";
                    }
                }
            
                if ($args) {
                    echo "\n" . $this->formatter->format('Arguments:', 'HEADER') . "\n" . $args;
                }
            }
            
            echo "\n";
        }
    }

    /**
     * loadTasks
     *
     * @param string $directory 
     * @return array $loadedTasks
     */
    public function loadTasks($directory = null)
    {
        if ($directory === null) {
            $directory = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Task';
        }
        
        $parent = new ReflectionClass('Doctrine_Task');
        
        $tasks = array();
        
        foreach ((array) $directory as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                    RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($it as $file) {
                $e = explode('.', $file->getFileName());
                if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                    require_once($file->getPathName());
                    
                    $className = 'Doctrine_Task_' . $e[0];
                    $class = new ReflectionClass($className);
                    
                    if ($class->isSubClassOf($parent)) {
                        $tasks[] = $e[0];
                    }
                }
            }
        }
        
        $this->tasks = array_merge($this->tasks, $tasks);
        
        return $this->tasks;
    }
}