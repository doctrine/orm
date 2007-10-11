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
abstract class Doctrine_Cli_Task
{
    public $taskName             =   null,
           $description          =   null,
           $arguments            =   array(),
           $requiredArguments    =   array(),
           $optionalArguments    =   array();
    
    abstract function execute();
    
    public function validate($args)
    {
        $this->arguments = $args;
        
        $requiredArguments = $this->getRequiredArguments();
        
        foreach ($requiredArguments as $arg) {
            if (!isset($args[$arg])) {
                throw new Doctrine_Cli_Exception('Required arguments missing. The follow arguments are required: ' . implode(', ', $requiredArguments));
            }
        }
        
        return true;
    }
    
    public function prepareArgs($args)
    {
        $args = array_values($args);
        
        $prepared = array();
        $requiredArguments = $this->getRequiredArguments();
        
        $count = 0;
        foreach ($requiredArguments as $key => $arg) {
            if (isset($args[$count])) {
                $prepared[$arg] = $args[$count];
            } else {
                $prepared[$arg] = null;
            }
            
            $count++;
        }
        
        $optionalArguments = $this->getOptionalArguments();
        
        foreach ($optionalArguments as $key => $arg) {
            if (isset($args[$count])) {
                $prepared[$arg] = $args[$count];
            } else {
                $prepared[$arg] = null;
            }
            
            $count++;
        }
        
        $this->arguments = $prepared;
        
        return $prepared;
    }
    
    public function getArgument($name)
    {
        return $this->arguments[$name];
    }
    
    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function getTaskName()
    {
        return $this->taskName;
    }
    
    public function getDescription()
    {
        return $this->description;
    }
    
    public function getRequiredArguments()
    {
        return array_keys($this->requiredArguments);
    }
    
    public function getOptionalArguments()
    {
        return array_keys($this->optionalArguments);
    }
    
    public function getRequiredArgumentsDescriptions()
    {
        return $this->requiredArguments;
    }
    
    public function getOptionalArgumentsDescriptions()
    {
        return $this->optionalArguments;
    }
    
    public function getSyntax()
    {    
        $syntax = './cli ' . $this->getTaskName();
        
        if ($required = $this->getRequiredArguments()) {
            $syntax .= ' <' . implode('> <', $required) . '>';
        }
        
        if ($optional = $this->getOptionalArguments()) {
             $syntax .= ' <' . implode('> <', $optional) . '>';
        }
        
        return $syntax;
    }
}