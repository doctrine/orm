<?php
/*
 *  $Id: Task.php 2761 2007-10-07 23:42:29Z zYne $
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
 * Doctrine_Cli_Task
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
    public function run($args)
    {
        if (!isset($args[1])) {
            echo $this->printTasks();
            return;
        }
        
        unset($args[0]);
        $taskName = str_replace('-', '_', $args[1]);
        unset($args[1]);
        
        $taskClass = 'Doctrine_Cli_Task_' . Doctrine::classify($taskName);
        
        if (class_exists($taskClass)) {
            $taskInstance = new $taskClass();
            $taskInstance->taskName = str_replace('_', '-', Doctrine::tableize(str_replace('Doctrine_Cli_Task_', '', $taskName)));
            
            $args = $taskInstance->prepareArgs($args);
            
            $taskInstance->validate($args);
            $taskInstance->execute($args);
        } else {
            throw new Doctrine_Cli_Exception('Cli task could not be found: '.$taskClass);
        }
    }
    
    public function printTasks()
    {
        $tasks = $this->loadTasks();
        
        echo "\nAvailable Doctrine Command Line Interface Tasks\n";
        echo str_repeat('-', 40)."\n\n";
        foreach ($tasks as $taskName)
        {
            $className = 'Doctrine_Cli_Task_' . $taskName;
            $taskInstance = new $className();
            $taskInstance->taskName = str_replace('_', '-', Doctrine::tableize($taskName));
            
            echo "Name: " . $taskInstance->getName() . "\n";
            echo "Description: " . $taskInstance->getDescription() . "\n";
            
            if ($requiredArguments = $taskInstance->getRequiredArguments()) {
                echo "Required Arguments: " . implode(', ', $requiredArguments) . "\n";
            }
            
            if ($optionalArguments = $taskInstance->getOptionalArguments()) {
                echo "Optional Arguments: " . implode(', ', $taskInstance->getOptionalArguments()) . "\n";
            }
            
            echo "Syntax: " . $taskInstance->getSyntax() . "\n";
            echo str_repeat('-', 40) . "\n\n";
        }
    }
    
    public function loadTasks($directory = null)
    {
        if ($directory === null) {
            $directory = dirname(__FILE__). DIRECTORY_SEPARATOR . 'Cli' . DIRECTORY_SEPARATOR . 'Task';
        }
        
        $parent = new ReflectionClass('Doctrine_Cli_Task');
        
        $tasks = array();
        
        foreach ((array) $directory as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                    RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($it as $file) {
                $e = explode('.', $file->getFileName());
                if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                    require_once($file->getPathName());
                    
                    $className = 'Doctrine_Cli_Task_' . $e[0];
                    $class = new ReflectionClass($className);
                    
                    if ($class->isSubClassOf($parent)) {
                        $tasks[] = $e[0];
                    }
                }
            }
        }
        
        return $tasks;
    }
}