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
    protected $tasks = array();
    protected $scriptName = null;
    protected $config = array();

    /**
     * __construct
     *
     * @param string $config 
     * @return void
     */
    public function __construct($config = array())
    {
        $this->config = $config;
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
        $this->scriptName = $args[0];
        
        if (!isset($args[1])) {
            echo $this->printTasks();
            return;
        }
        
        unset($args[0]);
        $taskName = str_replace('-', '_', $args[1]);
        unset($args[1]);
        
        $taskClass = 'Doctrine_Task_' . Doctrine::classify($taskName);
        
        if (class_exists($taskClass)) {
            $taskInstance = new $taskClass();
            
            $args = $this->prepareArgs($taskInstance, $args);
            
            $taskInstance->setArguments($args);
            
            if ($taskInstance->validate()) {
                $taskInstance->execute();
            }
        } else {
            throw new Doctrine_Cli_Exception('Cli task could not be found: '.$taskClass);
        }
    }

    /**
     * prepareArgs
     *
     * @param string $taskInstance 
     * @param string $args 
     * @return array $prepared
     */
    protected function prepareArgs($taskInstance, $args)
    {
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
    public function printTasks()
    {
        $tasks = $this->loadTasks();
        
        echo "\nAvailable Doctrine Command Line Interface Tasks\n";
        echo str_repeat('-', 40)."\n\n";
        
        foreach ($tasks as $taskName)
        {
            $className = 'Doctrine_Task_' . $taskName;
            $taskInstance = new $className();
            $taskInstance->taskName = str_replace('_', '-', Doctrine::tableize($taskName));
            
            echo $taskInstance->getDescription() . "\n";            
            
            $syntax  = "Syntax: ";
            
            $syntax .= $this->scriptName . ' ' . $taskInstance->getTaskName();
            
            if ($required = $taskInstance->getRequiredArguments()) {
                $syntax .= ' <' . implode('> <', $required) . '>';
            }

            if ($optional = $taskInstance->getOptionalArguments()) {
                 $syntax .= ' <' . implode('> <', $optional) . '>';
            }
            
            echo $syntax."\n";
            
            $args = null;
            if ($requiredArguments = $taskInstance->getRequiredArgumentsDescriptions()) {
                foreach ($requiredArguments as $name => $description) {
                    $args .= '*' . $name . ' - ' . $description."\n";
                }
            }
            
            if ($optionalArguments = $taskInstance->getOptionalArgumentsDescriptions()) {
                foreach ($optionalArguments as $name => $description) {
                    $args .= $name . ' - ' . $description."\n";
                }
            }
            
            if ($args) {
                echo "\nArguments (* = required):\n";
                echo $args;
            }
            
            echo "\n".str_repeat("-", 40)."\n";
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