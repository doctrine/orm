<?php
/*
 *  $Id: ErrorStack.php 1080 2007-02-10 18:17:08Z romanb $
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
 * Doctrine_Validator_ErrorStack
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 */
class Doctrine_Validator_ErrorStack extends Doctrine_Access implements Countable, IteratorAggregate
{

    /**
     * The errors of the error stack.
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Constructor
     *
     */
    public function __construct()
    {}

    /**
     * Adds an error to the stack.
     *
     * @param string $invalidFieldName
     * @param string $errorType
     */
    public function add($invalidFieldName, $errorCode = 'general')
    {
        $this->errors[$invalidFieldName][] = $errorCode;
    }

    /**
     * Removes all existing errors for the specified field from the stack.
     *
     * @param string $fieldName
     */
    public function remove($fieldName)
    {
        if (isset($this->errors[$fieldName])) {
            unset($this->errors[$fieldName]);
        }
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $name
     * @return unknown
     */
    public function get($fieldName)
    {
        return isset($this->errors[$fieldName]) ? $this->errors[$fieldName] : null;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $name
     */
    public function set($fieldName, $errorCode)
    {
        $this->add($fieldName, $errorCode);
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function contains($fieldName)
    {
        return array_key_exists($fieldName, $this->errors);
    }

    /**
     * Removes all errors from the stack.
     */
    public function clear()
    {
        $this->errors = array();
    }

    /** IteratorAggregate implementation */

    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function getIterator()
    {
        return new ArrayIterator($this->errors);
    }

    /** Countable implementation */

    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function count()
    {
        return count($this->errors);
    }
}
