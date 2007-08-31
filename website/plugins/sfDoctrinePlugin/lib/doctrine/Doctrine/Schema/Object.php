<?php
/*
 *  $Id: Object.php 1080 2007-02-10 18:17:08Z romanb $
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
Doctrine::autoload('Doctrine_Access');
/**
 * class Doctrine_Schema_Object
 * Catches any non-property call from child classes and throws an exception.
 *
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 */
abstract class Doctrine_Schema_Object extends Doctrine_Access implements IteratorAggregate, Countable
{

    protected $children   = array();

    protected $definition = array('name' => '');

    public function __construct(array $definition = array()) {
        foreach ($this->definition as $key => $val) {
            if (isset($definition[$key])) {
                $this->definition[$key] = $definition[$key];
            }
        }
    }

    public function get($name)
    {
        if ( ! array_key_exists($name, $this->definition)) {
            throw new Doctrine_Schema_Exception('Unknown definition '. $name);
        }
        return $this->definition[$name];

    }

    public function set($name, $value)
    {
        if ( ! array_key_exists($name, $this->definition)) {
            throw new Doctrine_Schema_Exception('Unknown definition '. $name);
        }
        $this->definition[$name] = $value;
    }

    public function contains($name)
    {
        return array_key_exists($name, $this->definition);
    }

    public function toArray()
    {
        return $this->definition;
    }
    /**
     *
     * @return int
     * @access public
     */
    public function count()
    {
        if ( ! empty($this->children)) {
            return count($this->children);
        }
        return count($this->definition);
    }

    /**
     * getIterator
     *
     * @return ArrayIterator
     * @access public
     */
    public function getIterator()
    {
        if ( ! empty($this->children)) {
            return new ArrayIterator($this->children);
        }
        return new ArrayIterator($this->definition);
    }
}
