<?php
/*
 *  $Id: Column.php 1392 2007-05-19 17:29:43Z zYne $
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
 * <http://www.phpdoctrine.com>.
 */
/**
 * Doctrine_Column
 * This class represents a database column
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1392 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Column extends Doctrine_Access implements IteratorAggregate, Countable
{
    /**
     * @var array $definition
     */
    protected $_definition = array(
                                'type'    => null,
                                'length'  => 0,
                                );
    /**
     * @var array $definition
     */
    public function __construct(array $definition = array())
    {
        $this->_definition = $definition;
    }
    /**
     * @return array
     */
    public function getDefinition()
    {
        return $this->_definition;
    }
    /**
     * contains
     *
     * @return boolean
     */
    public function contains($name) 
    {
        return isset($this->_definition[$name]);
    }
    /**
     * get
     *
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if ( ! isset($this->_definition[$name])) {
            return null;
        }
        
        return $this->_definition[$name];
    }
    /**
     * set
     *
     * @param string $name
     * @return mixed
     */
    public function set($name, $value)
    {
        $this->_definition[$name] = $value;
    }
    /**
     * @param string $field
     * @return array
     */
    public function getEnumValues()
    {
        if (isset($this->_definition['values'])) {
            return $this->_definition['values'];
        } else {
            return array();
        }
    }
    /**
     * enumValue
     *
     * @param string $field
     * @param integer $index
     * @return mixed
     */
    public function enumValue($index)
    {
        if ($index instanceof Doctrine_Null) {
            return $index;
        }

        return isset($this->_definition['values'][$index]) ? $this->_definition['values'][$index] : $index;
    }
    /**
     * enumIndex
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    public function enumIndex($field, $value)
    {
        $values = $this->getEnumValues($field);

        return array_search($value, $values);
    }
    /**
     * count
     *
     * @return integer
     */
    public function count()
    {
        return count($this->_definition);
    }
    /**
     * getIterator
     *
     * @return ArrayIterator
     */
    public function getIterator() 
    {
        return new ArrayIterator($this->_definition);
    }

}
