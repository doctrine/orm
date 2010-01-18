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
 * CLI Configuration class
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
class Configuration
{
    /**
     * @var array Configuration attributes
     */
    private $_attributes = array();

    /**
     * Defines a new configuration attribute
     *
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     *
     * @return Configuration This object instance
     */
    public function setAttribute($name, $value = null)
    {
        $this->_attributes[$name] = $value;
        
        if ($value === null) {
            unset($this->_attributes[$name]);
        }
        
        return $this;
    }
    
    /**
     * Retrieves a configuration attribute
     *
     * @param string $name Attribute name
     *
     * @return mixed Attribute value 
     */
    public function getAttribute($name)
    {
        return isset($this->_attributes[$name])
            ? $this->_attributes[$name] : null;
    }
    
    /**
     * Checks if configuration attribute is defined
     *
     * @param string $name Attribute name
     *
     * @return boolean TRUE if attribute exists, FALSE otherwise
     */
    public function hasAttribute($name)
    {
        return isset($this->_attributes[$name]);
    }
}