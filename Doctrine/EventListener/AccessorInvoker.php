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
 * Doctrine_EventListener_AccessorInvoker
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_EventListener_AccessorInvoker extends Doctrine_EventListener {
    /**
     * @var boolean $lockGetCall        a simple variable to prevent recursion
     */
    private $lockGetCall = false;
    /**
     * @var boolean $lockSetCall        a simple variable to prevent recursion
     */
    private $lockSetCall = false;
    /**
     * onGetProperty
     *
     * @param Doctrine_Record $record
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    public function onGetProperty(Doctrine_Record $record, $property, $value) {
        $method = 'get' . ucwords($property);

        if (method_exists($record, $method) && ! $this->lockGetCall) {
            $this->lockGetCall = true;

            $value = $record->$method($value);
            $this->lockGetCall = false;
            return $value;
        }
        return $value;
    }
    /**
     * onPreSetProperty
     *
     * @param Doctrine_Record $record
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    public function onSetProperty(Doctrine_Record $record, $property, $value) {
        $method = 'set' . ucwords($property);

        if (method_exists($record, $method) && ! $this->lockSetCall) {
            $this->lockSetCall = true;
            $value = $record->$method($value);
            $this->lockSetCall = false;
            return $value;
        }
        return $value;
    }
}
