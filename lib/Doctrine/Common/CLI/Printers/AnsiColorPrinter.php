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
 
namespace Doctrine\Common\CLI\Printers;

use Doctrine\Common\CLI\Style;

/**
 * CLI Output Printer for ANSI Color terminal
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class AnsiColorPrinter extends AbstractPrinter
{
    /**
     * @inheritdoc
     */
    protected function _initStyles()
    {
        $this->addStyles(array(
            'HEADER'  => new Style('DEFAULT', 'DEFAULT', array('BOLD' => true)),
            'ERROR'   => new Style('WHITE', 'RED', array('BOLD' => true)),
            'WARNING' => new Style('DEFAULT', 'YELLOW'),
            'KEYWORD' => new Style('BLUE', 'DEFAULT', array('BOLD' => true)),
            'REQ_ARG' => new Style('MAGENTA', 'DEFAULT', array('BOLD' => true)),
            'OPT_ARG' => new Style('CYAN', 'DEFAULT', array('BOLD' => true)),
            'INFO'    => new Style('GREEN', 'DEFAULT', array('BOLD' => true)),
            'COMMENT' => new Style('DEFAULT', 'MAGENTA'),
            'NONE'    => new Style(),
        ));
    }

    /**
     * @inheritdoc
     */
    public function format($message, $style = 'NONE')
    {
        if ( ! $this->_supportsColor()) {
            return $message;
        }

        $style = $this->getStyle($style);
        $str = $this->_getForegroundString($style) 
             . $this->_getBackgroundString($style);
        $styleSet = ($str != '');

        return $str . $message . ($styleSet ? chr(27) . '[0m' : '');
    }

    /**
     * Retrieves the ANSI string representation of requested color name
     *
     * @param Style $style Style
     * @return string
     */
    protected function _getBackgroundString(Style $style)
    {
        $background = $style->getBackground();

        if (empty($background)) {
            return '';
        }

        $esc = chr(27);

        switch (strtoupper($background)) {
            case 'BLACK': 
                return $esc . '[40m';
            case 'RED': 
                return $esc . '[41m';
            case 'GREEN':
                return $esc . '[42m';
            case 'YELLOW':
                return $esc . '[43m';
            case 'BLUE':
                return $esc . '[44m';
            case 'MAGENTA':
                return $esc . '[45m';
            case 'CYAN':
                return $esc . '[46m';
            case 'WHITE':
                return $esc . '[47m';
            case 'DEFAULT':
            default:
                return $esc . '[48m';
        }
    }
 
    /**
     * Retrieves the ANSI string representation of requested color name
     *
     * @param Style $style Style
     * @return string
     */
    protected function _getForegroundString(Style $style)
    {
        $foreground = $style->getForeground();

        if (empty($foreground)) {
            return '';
        }

        $str = chr(27) . '[' . $this->_getOptionsString($style);

        switch (strtoupper($foreground)) {
            case 'BLACK': 
                return $str . '30m';
            case 'RED': 
                return $str . '31m';
            case 'GREEN': 
                return $str . '32m';
            case 'YELLOW': 
                return $str . '33m';
            case 'BLUE': 
                return $str . '34m';
            case 'MAGENTA': 
                return $str . '35m';
            case 'CYAN': 
                return $str . '36m';
            case 'WHITE': 
                return $str . '37m';
            case 'DEFAULT_FGU': 
                return $str . '38m';
            case 'DEFAULT': 
            default:
                return $str . '39m';
        }
    }

    /**
     * Retrieves the ANSI string representation of requested options
     *
     * @param Style $style Style
     * @return string
     */
    protected function _getOptionsString(Style $style)
    {
        $options = $style->getOptions();

        if (empty($options)) {
            return '';
        }

        $str = '';

        foreach ($options as $name => $value) {
            if ($value) {
                $name = strtoupper($name);

                switch ($name) {
                    case 'BOLD':
                        $str .= '1;';
                        break;
                    case 'HALF': 
                        $str .= '2;';
                        break;
                    case 'UNDERLINE': 
                        $str .= '4;';
                        break;
                    case 'BLINK': 
                        $str .= '5;';
                        break;
                    case 'REVERSE': 
                        $str .= '7;';
                        break;
                    case 'CONCEAL':
                        $str .= '8;';
                        break;
                    default:
                        // Ignore unknown option
                        break;
                }
            }
        }

        return $str;
    }

    /**
     * Checks if the current Output Stream supports ANSI Colors
     *
     * @return boolean
     */
    private function _supportsColor()
    {
        return DIRECTORY_SEPARATOR != '\\' && 
               function_exists('posix_isatty') && 
               @posix_isatty($this->_stream); 
    }
}