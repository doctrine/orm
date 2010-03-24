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
 
namespace Doctrine\Common\CLI\Tasks;

use Doctrine\Common\CLI\AbstractNamespace,
    Doctrine\Common\CLI\TaskDocumentation;

/**
 * Base class for CLI Tasks.
 * Provides basic methods and requires implementation of methods that
 * each task should implement in order to correctly work.
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
abstract class AbstractTask
{
    /**
     * @var AbstractNamespace CLI Namespace
     */
    protected $_printer;

    /**
     * @var TaskDocumentation CLI Task Documentation
     */
    protected $_documentation;
    
    /**
     * @var array CLI Task arguments
     */
    protected $_arguments = array();
    
    /**
     * Constructor of CLI Task
     *
     * @param AbstractNamespace CLI Namespace
     */
    public function __construct(AbstractNamespace $namespace)
    {
        $this->_namespace = $namespace;
        $this->_documentation = new TaskDocumentation($namespace);
        
        // Complete the CLI Task Documentation creation
        $this->buildDocumentation();
    }

    /**
     * Retrieves the CLI Namespace
     *
     * @return AbstractNamespace
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }
    
    /**
     * Retrieves the CLI Task Documentation
     *
     * @return TaskDocumentation
     */
    public function getDocumentation()
    {
        return $this->_documentation;
    }
    
    /**
     * Defines the CLI Task arguments
     *
     * @param array $arguments CLI Task arguments
     *
     * @return AbstractTask
     */
    public function setArguments(array $arguments = array())
    {
        $this->_arguments = $arguments;
        
        return $this;
    }
    
    /**
     * Retrieves the CLI Task arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * Retrieves currently used CLI Output Printer
     *
     * @return AbstractPrinter
     */
    public function getPrinter()
    {
        return $this->_namespace->getPrinter();
    }

    /**
     * Retrieves current used CLI Configuration
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->_namespace->getConfiguration();
    }
    
    /**
     * Expose to CLI Output Printer the extended help of the given task.
     * This means it should detail all parameters, options and the meaning
     * of each one.
     * This method is executed when user types in CLI the following command:
     *
     *     [bash]
     *     ./doctrine task --help
     *
     */
    public function extendedHelp()
    {
        $this->getPrinter()->output($this->_documentation->getCompleteDocumentation());
    }
    
    /**
     * Expose to CLI Output Printer the basic help of the given task.
     * This means it should only expose the basic task call. It is also
     * executed when user calls the global help; so this means it should
     * not pollute the Printer.
     * Basic help exposure is displayed when task does not pass the validate
     * (which means when user does not type the required options or when given
     * options are invalid, ie: invalid option), or when user requests to have
     * description of all available tasks.
     * This method is executed when user uses the following commands:
     *
     *     [bash]
     *     ./doctrine task --invalid-option
     *     ./doctrine --help
     *
     */
    public function basicHelp()
    {
        $this->getPrinter()
             ->output($this->_documentation->getSynopsis())
             ->output(PHP_EOL)
             ->output('  ' . $this->_documentation->getDescription())
             ->output(PHP_EOL . PHP_EOL);
    }
    
    /**
     * Assures the given arguments matches with required/optional ones.
     * This method should be used to introspect arguments to check for
     * missing required arguments and also for invalid defined options.
     *
     * @return boolean
     */
    public function validate()
    {
        // TODO implement DAG here!
        return true;
    }
    
    /**
     * Safely execution of task.
     * Each CLI task should implement this as normal flow execution of
     * what is supposed to do.
     */
    abstract public function run();
    
    /**
     * Generate the CLI Task Documentation
     */
    abstract public function buildDocumentation();
}
