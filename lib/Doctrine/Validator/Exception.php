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
Doctrine::autoload('Doctrine_Exception');
/**
 * Doctrine_Validator_Exception
 *
 * @package     Doctrine
 * @subpackage  Validator
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Validator_Exception extends Doctrine_Exception implements Countable, IteratorAggregate
{
    /**
     * @var array $invalid
     */
    private $invalid = array();
    /**
     * @param Doctrine_Validator $validator
     */
    public function __construct(array $invalid)
    {
        $this->invalid = $invalid;
        parent::__construct($this->generateMessage());
    }

    public function getInvalidRecords()
    {
        return $this->invalid;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->invalid);
    }

    public function count()
    {
        return count($this->invalid);
    }
    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {

        return parent::__toString();
    }

    /**
     * Generate a message with all classes that have exceptions
     */
    private function generateMessage()
    {
        $message = "";
        foreach ($this->invalid as $record) {
           $message .= "Validaton error in class " . get_class($record) . " ";
        }
        return $message;
    }

    /**
     * This method will apply the value of the $function variable as a user_func 
     * to tall errorstack objects in the exception
     *
     * @param mixed Either string with function name or array with object, 
     * functionname. See call_user_func in php manual for more inforamtion
     */
    public function inspect($function)
    {
        foreach ($this->invalid as $record) {
            call_user_func($function, $record->getErrorStack());
        }
    }
}