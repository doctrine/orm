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
 * Doctrine_Sequence_Firebird
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Sequence_Firebird extends Doctrine_Sequence
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
        $sequence_name = $this->getSequenceName($seq_name);
        $query = 'SELECT GEN_ID('.$sequence_name.', 1) as the_value FROM RDB$DATABASE';
        $this->expectError('*');
        $result = $this->queryOne($query, 'integer');
        $this->popExpect();
        if (PEAR::isError($result)) {
            if ($ondemand) {
                $this->loadModule('Manager', null, true);
                // Since we are creating the sequence on demand
                // we know the first id = 1 so initialize the
                // sequence at 2
                $result = $this->manager->createSequence($seq_name, 2);
                if (PEAR::isError($result)) {
                    return $this->raiseError($result, null, null,
                        'on demand sequence could not be created', __FUNCTION__);
                } else {
                    // First ID of a newly created sequence is 1
                    // return 1;
                    // BUT generators are not always reset, so return the actual value
                    return $this->currID($seq_name);
                }
            }
        }
        return $result;
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
        throw new Doctrine_Sequence_Exception('method not implemented');
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
        $sequence_name = $this->getSequenceName($seq_name);
        $query = 'SELECT GEN_ID('.$sequence_name.', 0) as the_value FROM RDB$DATABASE';
        $value = $this->queryOne($query);
        if (PEAR::isError($value)) {
            return $this->raiseError($result, null, null,
                'Unable to select from ' . $seq_name, __FUNCTION__);
        }
        if (!is_numeric($value)) {
            return $this->raiseError(MDB2_ERROR, null, null,
                'could not find value in sequence table', __FUNCTION__);
        }
        return $value;
    }
}
