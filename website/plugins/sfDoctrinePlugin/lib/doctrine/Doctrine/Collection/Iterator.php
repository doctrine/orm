<?php
/*
 *  $Id: Iterator.php 1323 2007-05-10 23:46:09Z zYne $
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
 * Doctrine_Collection_Iterator
 * iterates through Doctrine_Collection
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1323 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Collection_Iterator implements Iterator
{
    /**
     * @var Doctrine_Collection $collection
     */
    protected $collection;
    /**
     * @var array $keys
     */
    protected $keys;
    /**
     * @var mixed $key
     */
    protected $key;
    /**
     * @var integer $index
     */
    protected $index;
    /**
     * @var integer $count
     */
    protected $count;

    /**
     * constructor
     * @var Doctrine_Collection $collection
     */
    public function __construct($collection)
    {
        $this->collection = $collection;
        $this->keys       = $this->collection->getKeys();
        $this->count      = $this->collection->count();
    }
    /**
     * rewinds the iterator
     *
     * @return void
     */
    public function rewind()
    {
        $this->index = 0;
        $i = $this->index;
        if (isset($this->keys[$i])) {
            $this->key   = $this->keys[$i];
        }
    }

    /**
     * returns the current key
     *
     * @return integer
     */
    public function key()
    {
        return $this->key;
    }
    /**
     * returns the current record
     *
     * @return Doctrine_Record
     */
    public function current()
    {
        return $this->collection->get($this->key);
    }
    /**
     * advances the internal pointer
     *
     * @return void
     */
    public function next()
    {
        $this->index++;
        $i = $this->index;
        if (isset($this->keys[$i])) {
            $this->key   = $this->keys[$i];
        }
    }
}
