<?php
/**
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
 * <http://www.phpdoctrine.net>.
 */

/**
 * Doctrine_Locator_Injectable
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Locator
 * @category    Locator
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL
 * @link        http://www.phpdoctrine.net
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Eevert Saukkokoski <dmnEe0@gmail.com>
 * @version     $Revision$
 * @since       1.0
 */
class Doctrine_Locator_Injectable
{
    /**
     * @var Doctrine_Locator      the locator object
     */
    protected $_locator;
    /**
     * @var array               an array of bound resources
     */
    protected $_resources = array();
    /**
     * @var Doctrine_Null $null     Doctrine_Null object, used for extremely fast null value checking
     */
    protected static $_null;
    /**
     * setLocator
     * this method can be used for setting the locator object locally
     *
     * @param Doctrine_Locator                the locator object
     * @return Doctrine_Locator_Injectable    this instance
     */
    public function setLocator(Doctrine_Locator $locator)
    {
        $this->_locator = $locator;
        return $this;
    }
    /**
     * getLocator
     * returns the locator associated with this object
     * 
     * if there are no locator locally associated then
     * this method tries to fetch the current global locator
     *
     * @return Doctrine_Locator
     */
    public function getLocator()
    {
        if ( ! isset($this->_locator)) {
            $this->_locator = Doctrine_Locator::instance();

        }
        return $this->_locator;
    }
    /**
     * locate
     * locates a resource by given name and returns it
     *
     * if the resource cannot be found locally this method tries
     * to use the global locator for finding the resource
     *
     * @see Doctrine_Locator::locate()
     * @throws Doctrine_Locator_Exception     if the resource could not be found
     * @param string $name                  the name of the resource
     * @return mixed                        the located resource
     */
    public function locate($name)
    {
        if (isset($this->_resources[$name])) {
            if (is_object($this->_resources[$name])) {
                return $this->_resources[$name];
            } else {
                // get the name of the concrete implementation
                $concreteImpl = $this->_resources[$name];
                
                return $this->getLocator()->get($concreteImpl);
            }
        } else {
            return $this->getLocator()->get($name);
        }
    }
    /**
     * bind
     * binds a resource to a name
     *
     * @param string $name      the name of the resource to bind
     * @param mixed $value      the value of the resource
     * @return Doctrine_Locator   this object
     */
    public function bind($name, $resource)
    {
        $this->_resources[$name] = $resource;
        
        return $this;    
    }

    /**
     * initNullObject
     * initializes the null object
     *
     * @param Doctrine_Null $null
     * @return void
     */
    public static function initNullObject(Doctrine_Null $null)
    {
        self::$_null = $null;
    }
    /**
     * getNullObject
     * returns the null object associated with this object
     *
     * @return Doctrine_Null
     */
    public static function getNullObject()
    {
        return self::$_null;
    }
}
