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

use Doctrine\Common\Cli\Printers\AbstractPrinter,
    Doctrine\Common\Cli\OptionGroup,
    Doctrine\Common\Cli\Option;

/**
 * CLI Task documentation
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class TaskDocumentation
{
    /** @var AbstractPrinter CLI Printer */
    private $_printer;
    
    /** @var string CLI Task name */
    private $_name;

    /** @var string CLI Task description */
    private $_description;
    
    /** @var array CLI Task Option Group */
    private $_optionGroup;
    
    /**
     * Constructs a new CLI Task Documentation
     *
     * @param AbstractPrinter CLI Printer
     */
    public function __construct(AbstractPrinter $printer)
    {
        $this->_printer = $printer;
        $this->_optionGroup = new OptionGroup(OptionGroup::CARDINALITY_M_N);
    }
    
    /**
     * Defines the CLI Task name
     *
     * @param string Task name
     * @return TaskDocumentation This object instance
     */
    public function setName($name)
    {
        $this->_name = $name;
        
        return $this;
    }
    
    /**
     * Retrieves the CLI Task name
     *
     * @return string Task name
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Defines the CLI Task description
     *
     * @param string Task description
     * @return TaskDocumentation This object instance
     */
    public function setDescription($description)
    {
        $this->_description = $description;
        
        return $this;
    }
    
    /**
     * Retrieves the CLI Task description
     *
     * @var string Task description
     */
    public function getDescription()
    {
        return $this->_description;
    }
    
    /**
     * Retrieves the CLI Task Option Group
     *
     * @return OptionGroup CLI Task Option Group
     */
    public function getOptionGroup()
    {
        return $this->_optionGroup;
    }
    
    /**
     * Includes a new CLI Option Group to the CLI Task documentation
     *
     * @param OptionGroup CLI Option Group
     * @return TaskDocumentation This object instance
     */
    public function addOption($option)
    {
        if ($option instanceof OptionGroup) {
            $this->_optionGroup->addOption($option);
        }
        
        return $this;
    }
    
    /**
     * Retrieves the synopsis of associated CLI Task
     *
     * @return string CLI Task synopsis
     */
    public function getSynopsis()
    {
        return $this->_printer->format($this->_name, 'KEYWORD') .  ' ' 
             . trim($this->_optionGroup->formatPlain($this->_printer));
    }
    
    /**
     * Retrieve the complete documentation of associated CLI Task
     *
     * @return string CLI Task complete documentation
     */
    public function getCompleteDocumentation()
    {
        $printer = $this->_printer;
    
        return $printer->format('Task: ')
             . $printer->format($this->_name, 'KEYWORD')
             . $printer->format(PHP_EOL)
             . $printer->format('Synopsis: ')
             . $this->getSynopsis()
             . $printer->format(PHP_EOL)
             . $printer->format('Description: ')
             . $printer->format($this->_description)
             . $printer->format(PHP_EOL)
             . $printer->format('Options: ')
             . $printer->format(PHP_EOL)
             . $this->_optionGroup->formatWithDescription($printer);
    }
}
