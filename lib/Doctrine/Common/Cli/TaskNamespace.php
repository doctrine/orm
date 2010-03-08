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
 * CLI Namespace class
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
class TaskNamespace extends AbstractNamespace
{
    /**
     * @var boolean CLI Tasks flag to check if they are already initialized
     */
    private $_initialized = false;

    /**
     * @var string CLI Namespace full name
     */
    private $_fullName = null;

    /**
     * @var string CLI Namespace name
     */
    private $_name = null;

    /**
     * @var array Available tasks
     */
    private $_tasks = array();

    /**
     * The CLI namespace
     *
     * @param string $name CLI Namespace name
     */
    public function __construct($name)
    {
        $this->_name = self::formatName($name);
    }

    /**
     * Retrieve an instantiated CLI Task by given its name.
     *
     * @param string $name CLI Task name
     *
     * @return AbstractTask
     */
    public function getTask($name)
    {
        // Check if task exists in namespace
        if ($this->hasTask($name)) {
            $taskClass = $this->_tasks[self::formatName($name)];

            return new $taskClass($this);
        }

        throw CliException::taskDoesNotExist($name, $this->getFullName());
    }

    /**
     * Retrieve all CLI Task in this Namespace.
     *
     * @return array
     */
    public function getTasks()
    {
        return $this->_tasks;
    }

    /**
     * Retrieve all defined CLI Tasks
     *
     * @return array
     */
    public function getAvailableTasks()
    {
        $tasks = parent::getAvailableTasks();

        foreach ($this->_tasks as $taskName => $taskClass) {
            $fullName = $this->getFullName() . ':' . $taskName;

            $tasks[$fullName] = $taskClass;
        }

        return $tasks;
    }

    /**
     * Add a single task to CLI Namespace.
     * Example of inclusion support to a single task:
     *
     *     [php]
     *     $cliOrmNamespace->addTask('my-custom-task', 'MyProject\Cli\Tasks\MyCustomTask');
     *
     * @param string $name CLI Task name
     * @param string $class CLI Task class (FQCN - Fully Qualified Class Name)
     *
     * @return TaskNamespace This object instance
     */
    public function addTask($name, $class)
    {
        $name = self::formatName($name);

        if ($this->hasTask($name)) {
            throw CliException::cannotOverrideTask($name);
        }

        return $this->overrideTask($name, $class);
    }

    /**
     * Overrides task on CLI Namespace.
     * Example of inclusion support to a single task:
     *
     *     [php]
     *     $cliOrmNamespace->overrideTask('schema-tool', 'MyProject\Cli\Tasks\MyCustomTask');
     *
     * @param string $name CLI Task name
     * @param string $class CLI Task class (FQCN - Fully Qualified Class Name)
     *
     * @return TaskNamespace This object instance
     */
    public function overrideTask($name, $class)
    {
        $name = self::formatName($name);

        $this->_tasks[$name] = $class;

        return $this;
    }

    /**
     * Check existance of a CLI Task
     *
     * @param string CLI Task name
     *
     * @return boolean TRUE if CLI Task if defined, false otherwise
     */
    public function hasTask($name)
    {
        $name = self::formatName($name);

        return isset($this->_tasks[$name]);
    }

    /**
     * Retrieves the CLI Namespace name
     *
     * @return string CLI Namespace name
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Retrieves the full CLI Namespace name
     *
     * @return string CLI Namespace full name
     */
    public function getFullName()
    {
        if ($this->_fullName === null) {
            $str = $this->_name;

            while (
                ($parentNamespace = $this->getParentNamespace()) !== null &&
                ! ($parentNamespace instanceof CliController)
            ) {
                $str = $parentNamespace->getFullName() . ':' . $str;
            }

            $this->_fullName = $str;
        }

        return $this->_fullName;
    }

    /**
     * Effectively instantiate and execute a given CLI Task
     *
     * @param string $name CLI Task name
     * @param array $arguments CLI Task arguments
     */
    public function runTask($name, $arguments = array())
    {
        try {
            $task = $this->getTask($name);

            // Merge global configuration if it exists
            if (($globalArgs = $this->getConfiguration()->getAttribute('globalArguments')) !== null) {
                $arguments = array_merge($globalArgs, $arguments);
            }

            $task->setArguments($arguments);

            if ((isset($arguments['help']) && $arguments['help']) || (isset($arguments['h']) && $arguments['h'])) {
                $task->extendedHelp(); // User explicitly asked for help option
            } else if (isset($arguments['basic-help']) && $arguments['basic-help']) {
                $task->basicHelp(); // User explicitly asked for basic help option
            } else if ($task->validate()) {
                $task->run();
            }
        } catch (CliException $e) {
            $message = $this->getFullName() . ':' . $name . ' => ' . $e->getMessage();
            $printer = $this->getPrinter();

            // If we want the trace of calls, append to error message
            if (isset($arguments['trace']) && $arguments['trace']) {
                $message .= PHP_EOL . PHP_EOL . $e->getTraceAsString();
            }

            $printer->writeln($message, 'ERROR');

            // Unable instantiate task or task is not valid
            if ($task !== null) {
                $printer->write(PHP_EOL);
                $task->basicHelp(); // Fallback of not-valid task arguments
            }

            $printer->write(PHP_EOL);
        }
    }
}