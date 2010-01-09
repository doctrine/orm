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

use Doctrine\Common\Cli\Printers\AbstractPrinter;

/**
 * CLI Option Group definition
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class OptionGroup
{
    /* CLI Option Group CARDINALITY */
    /**
     * Defines the cardinality 0..N to CLI Option Group.
     * This means options in this group are optional and you can 
     * define more than one CLI Option on a single command.
     */
    const CARDINALITY_0_N = 0; // [...] [...] [...]
    
    /**
     * Defines the cardinality 0..1 to CLI Option Group.
     * This means all options in this group are optional and you can 
     * define only one CLI Option on a single command.
     */
    const CARDINALITY_0_1 = 1; // [...|...|...]
    
    /**
     * Defines the cardinality 1..1 to CLI Option Group.
     * This means all options in this group are required and you must 
     * define only one CLI Option on a single command.
     */
    const CARDINALITY_1_1 = 2; // (...|...|...)
    
    /**
     * Defines the cardinality 1..N to CLI Option Group.
     * This means all options in this group are required and you must 
     * define at least one CLI Option on a single command.
     */
    const CARDINALITY_1_N = 3; // (... ... ...)
    
    /**
     * Defines the cardinality N..N to CLI Option Group.
     * This means all options in this group are required and you must 
     * define all CLI Options on a single command.
     */
    const CARDINALITY_N_N = 4; // ... ... ...
    
    /**
     * Defines the cardinality M..N to CLI Option Group.
     * This means all options in this group are either required or 
     * optional and you can CLI Options on a single command.
     * This is the option to skip CLI Option validation.
     */
    const CARDINALITY_M_N = 5; // ... ... ...


    /** @var integer Option Group cardinality */ 
    private $_cadinality;
    
    /** @var array Option Group list of CLI Options */
    private $_options;
    
    
    /**
     * Constructs a new CLI Option Group
     *
     * @param integer Option Group cardinality
     * @param array CLI Option Group options
     */
    public function __construct($cardinality, $options = array())
    {
        $this->_cardinality = $cardinality;
        $this->_options = $options;
    }
    
    /**
     * Retrieves the CLI Option Group cardinality
     *
     * @return integer Option Group cardinality
     */
    public function getCardinality()
    {
        return $this->_cardinality;
    }
    
    /**
     * Retrieves the CLI Option Group options
     *
     * @return array Option Group options
     */
    public function getOptions()
    {
        return $this->_options;
    }
    
    /**
     * Cleans the CLI Options inside this CLI Option Group
     *
     */
    public function clear()
    {
       $this->_options = array();
    }
    
    /**
     * Includes a new CLI Option to the Option Group
     *
     * @param Option|OptionGroup CLI Option or CLI Option Group
     * @return OptionGroup This object instance
     */
    public function addOption($option)
    {
        if ($option instanceof Option || $option instanceof OptionGroup) {
            $this->_options[] = $option;
        }
        
        return $this;
    }
    
    /**
     * Formats the CLI Option Group into a single line representation
     *
     * @param AbstractPrinter CLI Printer
     * @return string Single line string representation of CLI Option Group
     */
    public function formatPlain(AbstractPrinter $printer)
    {
        $numOptions = count($this->_options);
        
        if ($numOptions == 0) {
            return '';
        } 
        
        $style = $this->_getGroupOptionStyle();
        $shouldDisplayExtras = (
            $numOptions > 1 ||
            $this->_cardinality == self::CARDINALITY_0_1 || 
            $this->_cardinality == self::CARDINALITY_0_N
        );
        
        $str = ($shouldDisplayExtras) ? $printer->format($this->_startGroupDeclaration(), $style) : '';
        
        // Loop through all CLI Options defined in OptionGroup
        for ($i = 0; $i < $numOptions; $i++) {
            $option = $this->_options[$i];
            
            // Check for possible recursive OptionGroup
            if ($option instanceof OptionGroup) {
                // Simple increase nesting level by calling format recursively
                $str .= $option->formatPlain($printer);
            } else {
                // Expose the option formatted
                $str .= $printer->format((string) $option, $style);
            }
            
            // Possibly append content if needed
            if ($i < $numOptions - 1) {
                $str .= $printer->format($this->_separatorGroupDeclaration(), $style);
            }
        }
        
        $str .= ($shouldDisplayExtras) ? $printer->format($this->_endGroupDeclaration(), $style) : '';
        
        return $str;
    }
    
    /**
     * INTERNAL:
     * Defines the start Option Group declaration string
     *
     * @return string Start Option Group declaration string
     */
    private function _startGroupDeclaration()
    {
        $str = '';
        
        // Inspect cardinality of OptionGroup
        switch ($this->_cardinality) {
            case self::CARDINALITY_0_1:
            case self::CARDINALITY_0_N:
                $str .= '[';
                break;
                
            case self::CARDINALITY_1_1:
            case self::CARDINALITY_1_N:
                $str .= '(';
                break;
                
            case self::CARDINALITY_N_N:
            case self::CARDINALITY_M_N:
            default:
                // Does nothing
                break;
        }
        
        return $str;
    }
    
    /**
     * INTERNAL:
     * Defines the separator Option Group declaration string
     *
     * @return string Separator Option Group declaration string
     */
    private function _separatorGroupDeclaration()
    {
        $str = '';
    
        // Inspect cardinality of OptionGroup
        switch ($this->_cardinality) {
            case self::CARDINALITY_0_1:
            case self::CARDINALITY_1_1:
                $str .= ' | ';
                break;
                        
            case self::CARDINALITY_1_N:
            case self::CARDINALITY_N_N:
            case self::CARDINALITY_M_N:
                $str .= ' ';
                break;
                        
            case self::CARDINALITY_0_N:
                $str .= '] [';
                break;
                        
            default:
                // Does nothing
                break;
        }
        
        return $str;
    }
    
    /**
     * INTERNAL:
     * Defines the end Option Group declaration string
     *
     * @return string End Option Group declaration string
     */
    private function _endGroupDeclaration()
    {
        $str = '';
        
        // Inspect cardinality of OptionGroup
        switch ($this->_cardinality) {
            case self::CARDINALITY_0_1:
            case self::CARDINALITY_0_N:
                $str .= ']';
                break;
                
            case self::CARDINALITY_1_1:
            case self::CARDINALITY_1_N:
                $str .= ')';
                break;
                
            case self::CARDINALITY_N_N:
            case self::CARDINALITY_M_N:
            default:
                // Does nothing
                break;
        }
        
        return $str;
    }
    
    /**
     * INTERNAL:
     * Retrieve the Option Group style based on defined cardinality
     *
     * @return string CLI Style string representation
     */
    private function _getGroupOptionStyle()
    {
        $style = 'NONE';
        
        // Inspect cardinality of OptionGroup
        switch ($this->_cardinality) {
            case self::CARDINALITY_0_1:
            case self::CARDINALITY_0_N:
                $style = 'OPT_ARG';
                break;
                
            case self::CARDINALITY_1_1:
            case self::CARDINALITY_1_N:
            case self::CARDINALITY_N_N:
            case self::CARDINALITY_M_N:
                $style = 'REQ_ARG';
                break;
                
            default:
                // Does nothing
                break;
        }
        
        return $style;
    }
    
    /**
     * Formats the CLI Option Group into a multi-line list with respective description
     *
     * @param AbstractPrinter CLI Printer
     * @return string Multi-line string representation of CLI Option Group
     */
    public function formatWithDescription(AbstractPrinter $printer)
    {
        $numOptions = count($this->_options);
        
        if ($numOptions == 0) {
            return 'No available options' . PHP_EOL . PHP_EOL;
        } 
        
        $str = '';
        
        // Get list of required and optional and max length options
        list(
            $requiredOptions, $optionalOptions, $maxOptionLength
        ) = $this->_getOrganizedOptions(
            $this->_options, $this->_cardinality, 0
        );
        
        // Array-unique options
        $requiredOptions = array_unique($requiredOptions); 
        $optionalOptions = array_unique($optionalOptions);
        
        // TODO Sort options alphabetically
        
        // Displaying required options
        for ($i = 0, $l = count($requiredOptions); $i < $l; $i++) {
            $str .= $this->_displayOptionWithDescription(
                $printer, $requiredOptions[$i], 'REQ_ARG', $maxOptionLength
            );
            
            // Include extra line breaks between options
            $str .= PHP_EOL . PHP_EOL;
        }
        
        // Displaying optional options
        for ($i = 0, $l = count($optionalOptions); $i < $l; $i++) {
            $str .= $this->_displayOptionWithDescription(
                $printer, $optionalOptions[$i], 'OPT_ARG', $maxOptionLength
            );
            
            // Include extra line breaks between options
            $str .= PHP_EOL . PHP_EOL;
        }
        
        return $str;
    }
    
    /**
     * Organize the Options into arrays of required and optional options.
     * Also define the maximum length of CLI Options.
     *
     * @param array Array of CLI Option or CLI Option Group
     * @param integer Current CLI OptionGroup cardinality
     * @param integer Maximum length of CLI Options
     * @return array Array containing 3 indexes: required options, optional 
     *               options and maximum length of CLI Options
     */
    private function _getOrganizedOptions($options, $cardinality, $maxColumn)
    {
        // Calculate maximum length and also organize the
        // options into required and optional ones
        $numOptions = count($options);
        $requiredOptions = array();
        $optionalOptions = array();
        
        for ($i = 0; $i < $numOptions; $i++) {
            $option = $options[$i];
            
            // Check for possible recursive OptionGroup
            if ($option instanceof OptionGroup) {
                // Initialize OptionGroup options
                $groupRequiredOptions = array();
                $groupOptionalOptions = array();
            
                // Get nested information
                list(
                    $groupRequiredOptions, $groupOptionalOptions, $maxGroupColumn
                ) = $this->_getOrganizedOptions(
                    $option->getOptions(), $option->getCardinality(), $maxColumn
                );
                
                // Merge nested required and optional options
                $requiredOptions = array_merge($requiredOptions, $groupRequiredOptions);
                $optionalOptions = array_merge($optionalOptions, $groupOptionalOptions);
                
                // If OptionGroup length is bigger than the current maximum, update
                if ($maxColumn < $maxGroupColumn) {
                    $maxColumn = $maxGroupColumn;
                }
            } else {
                // Cardinality defines between optional or required options
                switch ($cardinality) {
                    case self::CARDINALITY_0_1:
                    case self::CARDINALITY_0_N:
                        $optionalOptions[] = $option;
                        break;
                        
                    case self::CARDINALITY_1_1:
                    case self::CARDINALITY_1_N:
                    case self::CARDINALITY_N_N:
                    case self::CARDINALITY_M_N:
                        $requiredOptions[] = $option;
                        break;
                        
                    default:
                        // Does nothing
                        break;
                }
                
                // Build Option string
                $optionStr = (string) $option;
                    
                // + 2 = aditional spaces after option
                $length = strlen($optionStr) + 2;
                
                if ($maxColumn < $length) {
                    $maxColumn = $length;
                }
            }
        }
        
        return array($requiredOptions, $optionalOptions, $maxColumn);
    }
    
    /**
     * INTERNAL:
     * Formats the CLI Option and also include the description
     *
     * @param AbstractPrinter CLI Printer
     * @param Option CLI Option to be formatted
     * @param string CLI Style string representation
     * @param integer Maximum CLI Option length
     * @return string Formats the current CLI Option line(s)
     */
    private function _displayOptionWithDescription($printer, $option, $style, $maxOptionLength)
    {    
        // Expose the option formatted
        $optionStr = (string) $option;
        
        // Format Option string
        $str = $printer->format($optionStr, $style);
        
        // Include missing spaces
        $str .= str_repeat(' ', $maxOptionLength - strlen($optionStr));
        
        // Calculate and display description
        $str .= str_replace(
            PHP_EOL, PHP_EOL . str_repeat(' ', $maxOptionLength), $option->getDescription()
        );
        
        return $str;
    }
}