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
 * @package     Doctrine
 * @url         http://www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @version     $Id$
 */



/**
 * class Doctrine_Schema_Object
 * Catches any non-property call from child classes and throws an exception.
 */
abstract class Doctrine_Schema_Object implements IteratorAggregate, Countable {

    protected $children   = array();

    protected $definition = array('name' => '');

    public function __construct(array $definition) {
        foreach($this->definition as $key => $val) {
            if(isset($definition[$key])) 
                $this->definition[$key] = $definition[$key];
        }
    }
    
    public function getName() {
        return $this->definition['name'];                          	
    }
    /**
     *
     * @return int
     * @access public
     */
    public function count() {
        if( ! empty($this->childs))
            return count($this->childs);

        return count($this->definition);
    }

    /**
     * getIterator
     *
     * @return ArrayIterator
     * @access public
     */
    public function getIterator() {
        if( ! empty($this->childs))
            return new ArrayIterator($this->childs);

        return new ArrayIterator($this->definition);
    }
}

