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

use Doctrine\Common\Util\Inflector;

/**
 * Abstract CLI Namespace class
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
abstract class AbstractNamespace
{
    /**
     * @var Configuration CLI Configuration instance
     */
    private $_configuration = null;

    /**
     * @var AbstractPrinter CLI Printer instance
     */
    private $_printer = null;

    /**
     * @var AbstractNamespace CLI Namespace instance
     */
    private $_parentNamespace = null;

    /**
     * @var array Available namespaces
     */
    private $_namespaces = array();

    /**
     * Add a single namespace to CLI.
     * Example of inclusion support to a single namespace:
     *
     *     [php]
     *     $cliOrmNamespace->addNamespace('my-custom-namespace');
     *
     * @param string $name CLI Namespace name
     *
     * @return CliController This object instance
     */
    public function addNamespace($name)
    {
        $name = self::formatName($name);

        if ($this->hasNamespace($name)) {
            throw CliException::cannotOverrideNamespace($name);
        }

        return $this->overrideNamespace($name);
    }

    /**
     * Overrides a namespace to CLI.
     * Example of inclusion support to a single namespace:
     *
     *     [php]
     *     $cli->overrideNamespace('orm');
     *
     * @param string $name CLI Namespace name
     *
     * @return AbstractNamespace Newly created CLI Namespace
     */
    public function overrideNamespace($name)
    {
        $taskNamespace = new TaskNamespace($name);

        $taskNamespace->setParentNamespace($this);
        $taskNamespace->setPrinter($this->_printer);
        $taskNamespace->setConfiguration($this->_configuration);

        $this->_namespaces[$taskNamespace->getName()] = $taskNamespace;

        return $taskNamespace;
    }

    /**
     * Retrieve CLI Namespace.
     * Example of usage:
     *
     *     [php]
     *     $cliOrmNamespace = $cli->getNamespace('ORM');
     *
     * @param string $name CLI Namespace name
     *
     * @return TaskNamespace CLI Namespace
     */
    public function getNamespace($name)
    {
        $name = self::formatName($name);

        return isset($this->_namespaces[$name])
            ? $this->_namespaces[$name] : null;
    }

    /**
     * Check existance of a CLI Namespace
     *
     * @param string CLI Namespace name
     *
     * @return boolean TRUE if namespace if defined, false otherwise
     */
    public function hasNamespace($name)
    {
        return ($this->getNamespace($name) !== null);
    }

    /**
     * Defines the parent CLI Namespace
     *
     * @return AbstractNamespace
     */
    public function setParentNamespace(AbstractNamespace $namespace)
    {
        $this->_parentNamespace = $namespace;

        return $this;
    }

    /**
     * Retrieves currently parent CLI Namespace
     *
     * @return AbstractNamespace
     */
    public function getParentNamespace()
    {
        return $this->_parentNamespace;
    }

    /**
     * Retrieve all defined CLI Tasks
     *
     * @return array
     */
    public function getAvailableTasks()
    {
        $tasks = array();

        foreach ($this->_namespaces as $namespace) {
            $tasks = array_merge($tasks, $namespace->getAvailableTasks());
        }

        return $tasks;
    }

    /**
     * Defines the CLI Output Printer
     *
     * @param AbstractPrinter $printer CLI Output Printer
     *
     * @return AbstractNamespace
     */
    public function setPrinter(Printers\AbstractPrinter $printer = null)
    {
        $this->_printer = $printer ?: new Printers\AnsiColorPrinter;

        return $this;
    }

    /**
     * Retrieves currently used CLI Output Printer
     *
     * @return AbstractPrinter
     */
    public function getPrinter()
    {
        return $this->_printer;
    }

    /**
     * Defines the CLI Configuration
     *
     * #param Configuration $configuration CLI Configuration
     *
     * @return AbstractNamespace
     */
    public function setConfiguration(Configuration $config)
    {
        $this->_configuration = $config;

        return $this;
    }

    /**
     * Retrieves currently used CLI Configuration
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * Formats the CLI Namespace name into a camel-cased name
     *
     * @param string $name CLI Namespace name
     *
     * @return string Formatted CLI Namespace name
     */
    public static function formatName($name)
    {
        return Inflector::classify($name);
    }
}