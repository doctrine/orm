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
Doctrine::autoload('Doctrine_Sequence');
/**
 * Doctrine_Sequence_Mssql
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Sequence_Mssql extends Doctrine_Sequence
{
    /**
     * Returns the next free id of a sequence
     *
     * @param string $seqName   name of the sequence
     * @param bool              when true missing sequences are automatic created
     *
     * @return integer          next id in the given sequence
     */
    public function nextID($seqName, $ondemand = true)
    {
        $sequence_name = $this->quoteIdentifier($this->getSequenceName($seq_name), true);
        $seqcol_name = $this->quoteIdentifier($this->options['seqcol_name'], true);
        $this->expectError(MDB2_ERROR_NOSUCHTABLE);
        if ($this->_checkSequence($sequence_name)) {
            $query = "SET IDENTITY_INSERT $sequence_name ON ".
                     "INSERT INTO $sequence_name ($seqcol_name) VALUES (0)";
        } else {
            $query = "INSERT INTO $sequence_name ($seqcol_name) VALUES (0)";
        }
        $result =& $this->_doQuery($query, true);
        $this->popExpect();
        if (PEAR::isError($result)) {
            if ($ondemand && !$this->_checkSequence($sequence_name)) {
                $this->loadModule('Manager', null, true);
                // Since we are creating the sequence on demand
                // we know the first id = 1 so initialize the
                // sequence at 2
                $result = $this->manager->createSequence($seq_name, 2);
                if (PEAR::isError($result)) {
                    return $this->raiseError($result, null, null,
                        'nextID: on demand sequence '.$seq_name.' could not be created');
                } else {
                    // First ID of a newly created sequence is 1
                    return 1;
                }
            }
            return $result;
        }
        $value = $this->lastInsertID($sequence_name);
        if (is_numeric($value)) {
            $query = "DELETE FROM $sequence_name WHERE $seqcol_name < $value";
            $result =& $this->_doQuery($query, true);
            if (PEAR::isError($result)) {
                $this->warnings[] = 'nextID: could not delete previous sequence table values from '.$seq_name;
            }
        }
        return $value;
    }
    /**
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param   string  name of the table into which a new row was inserted
     * @param   string  name of the field into which a new row was inserted
     */
    public function lastInsertID($table = null, $field = null)
    {
        $server_info = $this->getServerVersion();
        if (is_array($server_info)
            && !is_null($server_info['major'])
                && $server_info['major'] >= 8) {
                    $query = "SELECT SCOPE_IDENTITY()";
        } else {
                    $query = "SELECT @@IDENTITY";
        }

        return $this->queryOne($query, 'integer');
    }
    /**
     * Returns the current id of a sequence
     *
     * @param string $seqName   name of the sequence
     *
     * @return integer          current id in the given sequence
     */
    public function currID($seqName)
    {
        $this->warnings[] = 'database does not support getting current
            sequence value, the sequence value was incremented';
        return $this->nextID($seqName);
    }
}
