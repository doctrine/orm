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
 * <http://www.phpdoctrine.com>.
 */
/**
 * Doctrine_Access
 *
 * the purpose of Doctrine_Access is to provice array access
 * and property overload interface for subclasses
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
abstract class Doctrine_Access implements ArrayAccess {
    /**
     * setArray
     * @param array $array          an array of key => value pairs
     * @return Doctrine_Access
     */
    public function setArray(array $array) {
        foreach($array as $k=>$v):
            $this->set($k,$v);
        endforeach;

        return $this;
    }
    /**
     * __set -- an alias of set()
     * @see set, offsetSet
     * @param $name
     * @param $value
     */
    public function __set($name,$value) {
        $this->set($name,$value);
    }
    /**
     * __get -- an alias of get()
     * @see get,  offsetGet
     * @param mixed $name
     * @return mixed
     */
    public function __get($name) {
        return $this->get($name);
    }
    /**
     * @param mixed $offset
     * @return boolean -- whether or not the data has a field $offset
     */
    public function offsetExists($offset) {
        return (bool) isset($this->data[$offset]);
    }
    /**
     * offsetGet -- an alias of get()
     * @see get,  __get
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->get($offset);
    }
    /**
     * sets $offset to $value
     * @see set,  __set
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) {
        if( ! isset($offset)) {
            $this->add($value);
        } else
            $this->set($offset,$value);
    }
    /**
     * unset a given offset
     * @see set, offsetSet, __set
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        return $this->remove($offset);
    }
}
?>
