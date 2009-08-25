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

/**
 * CLI Output Style
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Style
{
    /**
     * @var string Background color
     */
    private $_background;
    
    /**
     * @var string Foreground color
     */    
    private $_foreground;
    
    /**
     * @var array Formatting options
     */
    private $_options = array();
    
    /**
     * @param string $foreground Foreground color name
     * @param string $background Background color name
     * @param array $options Formatting options
     */
    public function __construct($foreground = null, $background = null, $options = array())
    {
        $this->_foreground = strtoupper($foreground);
        $this->_background = strtoupper($background);
        $this->_options = $options;
    }
    
    /**
     * Retrieves the foreground color name
     *
     * @return string
     */
    public function getForeground()
    {
        return $this->_foreground;
    }
    
    /**
     * Retrieves the background color name
     *
     * @return string
     */
    public function getBackground()
    {
        return $this->_background;
    }
    
    /**
     * Retrieves the formatting options
     *
     * @return string
     */
    public function getOptions()
    {
        return $this->_options;
    }
}