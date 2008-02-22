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
 * <http://www.phpdoctrine.org>.
 */
Doctrine::autoload('Doctrine_Sequence');
/**
 * Doctrine_Sequence_Sqlite
 *
 * @package     Doctrine
 * @subpackage  Sequence
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Sequence_Sqlite extends Doctrine_Sequence
{
    /**
     * Returns the next free id of a sequence
     *
     * @param string $seqName   name of the sequence
     * @param bool $onDemand    when true missing sequences are automatic created
     *
     * @return integer          next id in the given sequence
     */
    public function nextId($seqName, $onDemand = true)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        $seqcolName   = $this->conn->quoteIdentifier($this->conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);

        $query        = 'INSERT INTO ' . $sequenceName . ' (' . $seqcolName . ') VALUES (NULL)';

        try {

            $this->conn->exec($query);

        } catch(Doctrine_Connection_Exception $e) {
            if ($onDemand && $e->getPortableCode() == Doctrine::ERR_NOSUCHTABLE) {
                try {
                    $this->conn->export->createSequence($seqName);
                    return $this->nextId($seqName, false);
                } catch(Doctrine_Exception $e) {
                    throw new Doctrine_Sequence_Exception('on demand sequence ' . $seqName . ' could not be created');
                }
            }
            throw $e;
        }

        $value = $this->lastInsertId();

        if (is_numeric($value)) {
            $query = 'DELETE FROM ' . $sequenceName . ' WHERE ' . $seqcolName . ' < ' . $value;
            try {
                $this->conn->exec($query);
            } catch(Doctrine_Exception $e) {
                throw new Doctrine_Sequence_Exception('could not delete previous sequence table values from ' . $seqName);
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
     * @return integer|boolean
     */
    public function lastInsertId($table = null, $field = null)
    {
        return $this->conn->getDbh()->lastInsertId();
    }

    /**
     * Returns the current id of a sequence
     *
     * @param string $seqName   name of the sequence
     *
     * @return integer          current id in the given sequence
     */
    public function currId($seqName)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        $seqcolName   = $this->conn->quoteIdentifier($this->conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);

        $query        = 'SELECT MAX(' . $seqcolName . ') FROM ' . $sequenceName;

        return (int) $this->conn->fetchOne($query);
    }
}
