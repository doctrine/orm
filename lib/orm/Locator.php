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
 * Doctrine_Locator
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
class Doctrine_Locator implements Countable, IteratorAggregate
{
    /**
     * @var array $_resources       an array of bound resources
     */
    protected $_resources = array();

    /**
     * @var string $_classPrefix    the default class prefix
     */
    protected $_classPrefix = 'Doctrine_';

    /** 
     * @var array $_instances       a pool of this object's instances
     */
    protected static $_instances = array();

    /**
     * Constructor. Provide an array of resources to set initial contents.
     *
     * @param array
     * @return void
     */
    public function __construct(array $defaults = null)
    {
        if (null !== $defaults) {
            foreach ($defaults as $name => $resource) {
                if ($resource instanceof Doctrine_Locator_Injectable) {
                    $resource->setLocator($this);
                }
                $this->_resources[$name] = $resource;
            }
        }
        self::$_instances[] = $this;
    }

    /** 
     * instance
     *
     * @return Sensei_Locator
     */
    public static function instance()
    {
        if (empty(self::$_instances)) {
            $obj = new Doctrine_Locator();
        }
        return current(self::$_instances);
    }

    /**
     * setClassPrefix
     *
     * @param string $prefix
     */
    public function setClassPrefix($prefix) 
    {
        $this->_classPrefix = $prefix;
    }

    /**
     * getClassPrefix
     *
     * @return string
     */
    public function getClassPrefix()
    {
        return $this->_classPrefix;
    }

    /**
     * contains
     * checks if a resource exists under the given name
     *
     * @return boolean      whether or not given resource name exists
     */
    public function contains($name)
    {
        return isset($this->_resources[$name]);
    }

    /**
     * bind
     * binds a resource to a name
     *
     * @param string $name      the name of the resource to bind
     * @param mixed $value      the value of the resource
     * @return Sensei_Locator   this object
     */
    public function bind($name, $value)
    {
        $this->_resources[$name] = $value;
        
        return $this;
    }

    /**
     * locate
     * locates a resource by given name and returns it
     *
     * @throws Doctrine_Locator_Exception     if the resource could not be found
     * @param string $name      the name of the resource
     * @return mixed            the located resource
     */
    public function locate($name)
    {
        if (isset($this->_resources[$name])) {
            return $this->_resources[$name];
        } else {
            $className = $name;

            if ( ! class_exists($className)) {

                $name = explode('.', $name);
                $name = array_map('strtolower', $name);
                $name = array_map('ucfirst', $name);
                $name = implode('_', $name);
                
                $className = $this->_classPrefix . $name;
                
                if ( ! class_exists($className)) {
                    throw new Doctrine_Locator_Exception("Couldn't locate resource " . $className);
                }
            }

            $this->_resources[$name] = new $className();

            if ($this->_resources[$name] instanceof Doctrine_Locator_Injectable) {
                $this->_resources[$name]->setLocator($this);
            }

            return $this->_resources[$name];
        }

        throw new Doctrine_Locator_Exception("Couldn't locate resource " . $name);
    }

    /**
     * count
     * returns the number of bound resources associated with
     * this object
     *
     * @see Countable interface
     * @return integer              the number of resources
     */
    public function count()
    {
        return count($this->_resources);
    }

    /**
     * getIterator
     * returns an ArrayIterator that iterates through all 
     * bound resources
     *
     * @return ArrayIterator    an iterator for iterating through 
     *                          all bound resources
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_resources);
    }
}
