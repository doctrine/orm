<?php
/*
 *  $Id: Record.php 1298 2007-05-01 19:26:03Z zYne $
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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Record_Filter_Compound
 *
 * @package     Doctrine
 * @subpackage  Record
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 1298 $
 */
class Doctrine_Record_Filter_Compound extends Doctrine_Record_Filter
{
    protected $_aliases = array();

    public function __construct(array $aliases)
    {
        $this->_aliases = $aliases;
    }
    public function init()
    {
    	// check that all aliases exist
    	foreach ($this->_aliases as $alias) {
            $this->_table->getRelation($alias);
    	}
    }

    /**
     * filterSet
     * defines an implementation for filtering the set() method of Doctrine_Record
     *
     * @param mixed $name                       name of the property or related component
     */
    public function filterSet(Doctrine_Record $record, $name, $value)
    {
        foreach ($this->_aliases as $alias) {
            if ( ! $record->exists()) {
                if (isset($record[$alias][$name])) {
                    $record[$alias][$name] = $value;
                    
                    return $record;
                }
            } else {
                // we do not want to execute N + 1 queries here, hence we cannot use get()
                if (($ref = $record->reference($alias)) !== null) {
                    if (isset($ref[$name])) {
                        $ref[$name] = $value;
                    }
                    
                    return $record;
                }
            }
        }
    }

    /**
     * filterGet
     * defines an implementation for filtering the get() method of Doctrine_Record
     *
     * @param mixed $name                       name of the property or related component
     */
    public function filterGet(Doctrine_Record $record, $name)
    {
        foreach ($this->_aliases as $alias) {
            if ( ! $record->exists()) {
                if (isset($record[$alias][$name])) {
                    return $record[$alias][$name];
                }
            } else {
                // we do not want to execute N + 1 queries here, hence we cannot use get()
                if (($ref = $record->reference($alias)) !== null) {
                    if (isset($ref[$name])) {
                        return $ref[$name];
                    }
                }
            }
        }
    }
}