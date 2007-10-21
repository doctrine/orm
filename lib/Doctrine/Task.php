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
 * Doctrine_Task
 * 
 * Abstract class used for writing Doctrine Tasks
 *
 * @package     Doctrine
 * @subpackage  Task
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2761 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
abstract class Doctrine_Task
{
    public $dispatcher           =   null,
           $taskName             =   null,
           $description          =   null,
           $arguments            =   array(),
           $requiredArguments    =   array(),
           $optionalArguments    =   array();

    /**
     * __construct
     *
     * Since this is an abstract classes that extend this must follow a patter of Doctrine_Task_{TASK_NAME}
     * This is what determines the task name for executing it.
     *
     * @return void
     */
    public function __construct($dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
        
        $this->taskName = str_replace('_', '-', Doctrine::tableize(str_replace('Doctrine_Task_', '', get_class($this))));
    }

    /**
     * notify
     *
     * @param string $notification 
     * @return void
     */
    public function notify()
    {
        if (is_object($this->dispatcher) && method_exists($this->dispatcher, 'notify')) {
            $args = func_get_args();
            
            return call_user_func_array(array($this->dispatcher, 'notify'), $args);
        } else {
            return $notification;
        }
    }

    /**
     * ask
     *
     * @return void
     */
    public function ask()
    {
        $args = func_get_args();
        
        call_user_func_array(array($this, 'notify'), $args);
        
        $answer = strtolower(trim(fgets(STDIN)));
        
        return $answer;
    }

    /**
     * execute
     *
     * Override with each task class
     *
     * @return void
     * @abstract
     */
    abstract function execute();

    /**
     * validate
     *
     * Validates that all required fields are present
     *
     * @return bool true
     */
    public function validate()
    {
        $requiredArguments = $this->getRequiredArguments();
        
        foreach ($requiredArguments as $arg) {
            if ( ! isset($this->arguments[$arg])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * addArgument
     *
     * @param string $name 
     * @param string $value 
     * @return void
     */
    public function addArgument($name, $value)
    {
        $this->arguments[$name] = $value;
    }

    /**
     * getArgument
     *
     * @param string $name 
     * @param string $default 
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        if (isset($this->arguments[$name]) && $this->arguments[$name] !== null) {
            return $this->arguments[$name];
        } else {
            return $default;
        }
    }

    /**
     * getArguments
     *
     * @return array $arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * setArguments
     *
     * @param array $args 
     * @return void
     */
    public function setArguments(array $args)
    {
        $this->arguments = $args;
    }

    /**
     * getTaskName
     *
     * @return string $taskName
     */
    public function getTaskName()
    {
        return $this->taskName;
    }

    /**
     * getDescription
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * getRequiredArguments
     *
     * @return array $requiredArguments
     */
    public function getRequiredArguments()
    {
        return array_keys($this->requiredArguments);
    }

    /**
     * getOptionalArguments
     *
     * @return array $optionalArguments
     */
    public function getOptionalArguments()
    {
        return array_keys($this->optionalArguments);
    }

    /**
     * getRequiredArgumentsDescriptions
     *
     * @return array $requiredArgumentsDescriptions
     */
    public function getRequiredArgumentsDescriptions()
    {
        return $this->requiredArguments;
    }

    /**
     * getOptionalArgumentsDescriptions
     *
     * @return array $optionalArgumentsDescriptions
     */
    public function getOptionalArgumentsDescriptions()
    {
        return $this->optionalArguments;
    }
}