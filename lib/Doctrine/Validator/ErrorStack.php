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
Doctrine::autoload('Doctrine_Access');
/**
 * Doctrine_Validator_ErrorStack
 *
 * @author      Konsta Vesterinen
 * @license     LGPL
 * @package     Doctrine
 */
class Doctrine_Validator_ErrorStack extends Doctrine_Access implements Countable, IteratorAggregate {
    
    private $errors = array();

    public function merge($stack) {
        if(is_array($stack)) {
            $this->errors = array_merge($this->errors, $stack);
        }
    }
    
    public function get($name) {
        if(isset($this->errors[$name]))
            return $this->errors[$name];
        
        return null;
    }

    public function set($name, $value) {
        $this->errors[$name] = $value;
    }
    
    public function getIterator() {
        return new ArrayIterator($this->errors);
    }
    public function count() {
        return count($this->errors);
    }
}
