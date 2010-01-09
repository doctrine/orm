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
 * CLI Option definition
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Option
{
    /** @var string Option name */
    private $_name;
    
    /** @var string Option default value */
    private $_defaultValue;
    
    /** @var string Option description */
    private $description;
    
    /**
     * Constructs a CLI Option
     *
     * @param string Option name
     * @param integer Option type
     * @param string Option description
     */
    public function __construct($name, $defaultValue, $description)
    {
        $this->_name = $name;
        $this->_defaultValue = $defaultValue;
        $this->_description = $description;
    }
    
    /**
     * Retrieves the CLI Option name
     *
     * @return string Option name
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Retrieves the CLI Option default value
     *
     * @return mixed Option default value
     */
    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }
    
    /**
     * Retrieves the CLI Option description
     *
     * @return string Option description
     */
    public function getDescription()
    {
        return $this->_description;
    }
    
    /**
     * Converts the Option instance into a string representation
     *
     * @return string CLI Option representation in string
     */
    public function __toString()
    {
        $defaultValue = ( ! is_null($this->_defaultValue)) 
            ? '=' . (is_array($this->_defaultValue) ? implode(',', $this->_defaultValue) : $this->_defaultValue) 
            : '';
    
        return '--' . $this->_name . $defaultValue;
    }
}