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
 * @subpackage  Task
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2761 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
abstract class Doctrine_Task
{
    public $taskName             =   null,
           $description          =   null,
           $arguments            =   array(),
           $requiredArguments    =   array(),
           $optionalArguments    =   array();
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->taskName = str_replace('_', '-', Doctrine::tableize(str_replace('Doctrine_Task_', '', get_class($this))));
    }
    
    /**
     * execute
     *
     * Override with each task class
     *
     * @return void
     * @author Jonathan H. Wage
     */
    abstract function execute();
    
    /**
     * validate
     *
     * Validates that all required fields are present
     *
     * @return void
     */
    public function validate()
    {
        $requiredArguments = $this->getRequiredArguments();
        
        foreach ($requiredArguments as $arg) {
            if (!isset($this->arguments[$arg])) {
                throw new Doctrine_Task_Exception('Required arguments missing. The follow arguments are required: ' . implode(', ', $requiredArguments));
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
     * @return void
     */
    public function getArgument($name, $default = null)
    {
        if (isset($this->arguments[$name]) && $this->arguments[$name]) {
            return $this->arguments[$name];
        } else {
            return $default;
        }
    }
    
    /**
     * getArguments
     *
     * @return void
     */
    public function getArguments()
    {
        return $this->arguments;
    }
    
    /**
     * setArguments
     *
     * @param string $args 
     * @return void
     */
    public function setArguments($args)
    {
        $this->arguments = $args;
    }
    
    /**
     * getTaskName
     *
     * @return void
     */
    public function getTaskName()
    {
        return $this->taskName;
    }
    
    /**
     * getDescription
     *
     * @return void
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * getRequiredArguments
     *
     * @return void
     */
    public function getRequiredArguments()
    {
        return array_keys($this->requiredArguments);
    }
    
    /**
     * getOptionalArguments
     *
     * @return void
     */
    public function getOptionalArguments()
    {
        return array_keys($this->optionalArguments);
    }
    
    /**
     * getRequiredArgumentsDescriptions
     *
     * @return void
     */
    public function getRequiredArgumentsDescriptions()
    {
        return $this->requiredArguments;
    }
    
    /**
     * getOptionalArgumentsDescriptions
     *
     * @return void
     * @author Jonathan H. Wage
     */
    public function getOptionalArgumentsDescriptions()
    {
        return $this->optionalArguments;
    }
}