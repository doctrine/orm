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
        $this->_maxColumnSize = 80;
        
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
            'ERROR'   => new Style(),
            'INFO'    => new Style(),
            'COMMENT' => new Style(),
            'HEADER'  => new Style(),
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
        $name = strtoupper($name);
        
        return isset($this->_styles[$name]) ? $this->_styles[$name] : null;
    }
    
    /**
     * Sets the maximum column size (defines the CLI margin).
     *
     * @param integer $maxColumnSize The maximum column size for a message
     */
    public function setMaxColumnSize($maxColumnSize)
    {
        $this->_maxColumnSize = $maxColumnSize;
    }

    /**
     * Writes to the output stream, formatting it by applying the defined style.
     *
     * @param string $message Message to be outputted
     * @param mixed $style Optional style to be applied in message
     */
    public function write($message, $style = 'NONE')
    {
        $style = is_string($style) ? $this->getStyle($style) : $style;
    
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
    
    /**
     * Formats the given message with the defined style.
     *
     * @param string $message Message to be formatted
     * @param mixed $style Style to be applied in message
     * @return string Formatted message
     */
    abstract public function format($message, Style $style);
}