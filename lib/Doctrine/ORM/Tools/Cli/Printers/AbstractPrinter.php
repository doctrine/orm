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
 
namespace Doctrine\ORM\Tools\Cli\Printers;
error_reporting(E_ALL | E_STRICT);
use Doctrine\ORM\Tools\Cli\Style;

/**
 * CLI Output Printer.
 * Abstract class responsable to provide basic methods to support output 
 * styling and excerpt limited by output margin.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractPrinter
{
    /**
     * Defines the tab length
     */
    const TAB_LENGTH = 8;
    
    /**
     * @var resource Output Stream
     */
    protected $_stream;

    /**
     * @var integer Maximum column size
     */
    protected $_maxColumnSize;

    /**
     * @var array Array of Styles
     */
    protected $_styles;

    /**
     * Creates an instance of Printer
     *
     * @param resource $stream Output Stream
     */
    public function __construct($stream = STDOUT)
    {
        $this->_stream = $stream;
        $this->setMaxColumnSize(0);
        
        $this->_initStyles();
    }
    
    /**
     * Initializes Printer Styles
     *
     */
    protected function _initStyles()
    {
        // Defines base styles
        $this->addStyles(array(
            'HEADER'  => new Style(),
            'ERROR'   => new Style(),
            'WARNING' => new Style(),
            'KEYWORD' => new Style(),
            'REQ_ARG' => new Style(),
            'OPT_ARG' => new Style(),
            'INFO'    => new Style(),
            'COMMENT' => new Style(),
            'NONE'    => new Style(),
        ));
    }
    
    /**
     * Add a collection of styles to the Printer.
     * To include them, just call the method with the following structure:
     *
     *    [php]
     *    $printer->addStyles(array(
     *        'ERROR' => new Style('BLACK', 'DEFAULT', array('BOLD' => true)),
     *        ...
     *    ));
     *
     * @param array $tasks CLI Tasks to be included
     */
    public function addStyles($styles)
    {
        foreach ($styles as $name => $style) {
            $this->addStyle($name, $style);
        }
    }
    
    /**
     * Add a single Style to Printer.
     * Example of inclusion to support a new Style:
     *
     *     [php]
     *     $printer->addStyle('ERROR', new Style('BLACK', 'DEFAULT', array('BOLD' => true)));
     *
     * @param string $name Style name
     * @param Style $style Style instance
     */
    public function addStyle($name, Style $style)
    {
        $this->_styles[strtoupper($name)] = $style;
    }
    
    /**
     * Retrieves a defined Style.
     *
     * @return Style
     */
    public function getStyle($name)
    {
        if (is_string($name)) {
            $name = strtoupper($name);
            return isset($this->_styles[$name]) ? $this->_styles[$name] : null;
        } else {
            return $name;
        }
    }
    
    /**
     * Sets the maximum column size (defines the CLI margin).
     *
     * @param integer $maxColumnSize The maximum column size for a message.
     *                               Must be higher than 25 and assigns default
     *                               value (if invalid) to 80 columns.
     */
    public function setMaxColumnSize($maxColumnSize)
    {
        $this->_maxColumnSize = ($maxColumnSize > 25) ? $maxColumnSize : 80;
    }

    /**
     * Writes to the output stream, formatting it by applying the defined style.
     *
     * @param string $message Message to be outputted
     * @param mixed $style Optional style to be applied in message
     */
    public function write($message, $style = 'NONE')
    {
        fwrite($this->_stream, $this->format($message, $style));
        
        return $this;
    }
    
    /**
     * Writes a line to the output stream, formatting it by applying the defined style.
     *
     * @param string $message Message to be outputted
     * @param mixed $style Optional style to be applied in message
     */
    public function writeln($message, $style = 'NONE')
    {
        return $this->write($message . PHP_EOL, $style);
    }
    
    public function writeTaskDocumentation($taskName, $arguments = array(), $description, $options = array())
    {
        // Writting task name
        $this->write('Task: ', 'HEADER')->writeln($taskName, 'KEYWORD');
        
        // Synopsis
        $this->writeln('Synopsis:', 'HEADER');
        $this->writeSynopsis($taskName, $arguments);
        
        // We need to split the description according to maximum column size
        $this->writeln('Description:', 'HEADER');
        $this->writeDescription($description);        
        
        // Find largest length option name (it is mandatory for tab spacing)
        $lengths = array_map(create_function('$v', 'return strlen($v["name"]);'), $options);
        sort($lengths, SORT_NUMERIC);
        
        $highestLength = end($lengths);
        $maxTabs = ceil($highestLength / self::TAB_LENGTH);
        
        // Options (required + optional arguments)
        $this->writeln('Options:', 'HEADER');
        
        for ($i = 0, $len = count($options); $i < $len; $i++) {
            $this->writeOption($options[$i], $maxTabs, $highestLength);
            
            if ($i != $len - 1) {
                $this->write(PHP_EOL);
            }
        }
    }
    
    public function writeSynopsis($taskName, $arguments = array())
    {
        // Required arguments
        $requiredArguments = '';
          
        if (isset($arguments['required'])) {
            $requiredArguments = ' ' . ((is_array($arguments['required']))
                ? implode(' ', $arguments['required']) : $arguments['required']);
        }
        
        // Optional arguments
        $optionalArguments = '';
        
        if (isset($arguments['optional'])) {
            $optionalArguments = ' ' . ((is_array($arguments['optional']))
                ? implode(' ', $arguments['optional']) : $arguments['optional']);
        }
        
        $this->write($taskName, 'KEYWORD');
        
        if (($l = strlen($taskName . $requiredArguments)) > $this->_maxColumnSize) {
            $this->write(PHP_EOL);
        }
        
        $this->write(' ' . $requiredArguments, 'REQ_ARG');
        
        if (($l + strlen($optionalArguments)) > $this->_maxColumnSize) {
            $this->write(PHP_EOL);
        }
        
        $this->write(' ' . $optionalArguments, 'OPT_ARG');
        
        $this->write(PHP_EOL);
    }
    
    protected function writeDescription($description)
    {
        $descriptionLength = strlen($description);
        $startPos = 0;
        $maxSize = $endPos = $this->_maxColumnSize;
        
        // Description
        while ($startPos < $descriptionLength) {
        	$descriptionPart = trim(substr($description, $startPos, $endPos + 1));
        	$endPos = (($l = strlen($descriptionPart)) > $maxSize) 
                ? strrpos($descriptionPart, ' ') : $l;
            $endPos = ($endPos === false) ? strlen($description) : $endPos + 1; 
            
            // Write description line
            $this->writeln(trim(substr($description, $startPos, $endPos)));
            
            $startPos += $endPos;
            $endPos = $maxSize;
        }
    }
    
    protected function writeOption($option, $maxTabs, $highestLength)
    {
        // Option name
        $this->write(
            $option['name'], 
            (isset($option['required']) && $option['required']) ? 'REQ_ARG' : 'OPT_ARG'
        );
            
        // Tab spacing
        $optionLength = strlen($option['name']);
        $tabs = floor($optionLength / self::TAB_LENGTH);
        $decrementer = 0;
        
        //echo '[' .$tabs. ']';
            
        if (($optionLength % self::TAB_LENGTH != 0)) {
            $decrementer = 1;
            //$tabs--;
        }
            
        $this->write(str_repeat(" ", ($maxTabs - $tabs) * self::TAB_LENGTH));
            
        // Description
        $descriptionLength = strlen($option['description']);
        
        $startPos = 0;
        $maxSize = $endPos = $this->_maxColumnSize - ($maxTabs * self::TAB_LENGTH);
            
        while ($startPos < $descriptionLength) {
            $descriptionPart = trim(substr($option['description'], $startPos, $endPos + 1));
            $endPos = (($l = strlen($descriptionPart)) >= $maxSize) 
                ? strrpos($descriptionPart, ' ') : $l;
            $endPos = ($endPos === false) ? strlen($option['description']) : $endPos + 1; 
            $descriptionLine = (($startPos != 0) ? str_repeat(" ", $maxTabs * self::TAB_LENGTH) : '') 
                . trim(substr($option['description'], $startPos, $endPos));
            $this->writeln($descriptionLine);
            
            $startPos += $endPos;
            $endPos = $maxSize;
        }
    }
    
    /**
     * Formats the given message with the defined style.
     *
     * @param string $message Message to be formatted
     * @param mixed $style Style to be applied in message
     * @return string Formatted message
     */
    abstract public function format($message, $style);
}