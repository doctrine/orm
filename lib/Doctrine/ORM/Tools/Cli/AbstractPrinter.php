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
 
namespace Doctrine\ORM\Tools\Cli;

abstract class AbstractPrinter
{
    protected $_stream;

    protected $_maxColumnSize;

    protected $_styles;

    public function __construct($stream = STDOUT)
    {
        $this->_stream = $stream;
        $this->_maxColumnSize = 80;
        
        $this->_initStyles();
    }
    
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
    
    public function addStyles($styles)
    {
        foreach ($styles as $name => $style) {
            $this->addStyle($name, $style);
        }
    }
    
    public function addStyle($name, Style $style)
    {
        $this->_styles[strtoupper($name)] = $style;
    }
    
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
     * Writes to output stream the message, formatting it by applying the defined style.
     *
     * @param string $message Message to be outputted
     * @param mixed $style Optional style to be applied in message
     */
    public function write($message, $style = 'ERROR')
    {
        $style = is_string($style) ? $this->getStyle($style) : $style;
    
        fwrite($this->_stream, $this->format($message, $style));
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