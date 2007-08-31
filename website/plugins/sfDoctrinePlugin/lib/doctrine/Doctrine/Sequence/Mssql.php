<?php
/*
 *  $Id: Mssql.php 1934 2007-07-05 22:42:32Z zYne $
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
 * @version     $Revision: 1934 $
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
    public function nextId($seqName, $onDemand = true)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        $seqcolName   = $this->conn->quoteIdentifier($this->conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);


        if ($this->checkSequence($sequenceName)) {
            $query = 'SET IDENTITY_INSERT ' . $sequenceName . ' OFF '
                   . 'INSERT INTO ' . $sequenceName . ' DEFAULT VALUES';
        } else {
            $query = 'INSERT INTO ' . $sequenceName . ' (' . $seqcolName . ') VALUES (0)';
        }
        
        try {

            $this->conn->exec($query);

        } catch(Doctrine_Connection_Exception $e) {
            if ($onDemand && $e->getPortableCode() == Doctrine::ERR_NOSUCHTABLE) {
                // Since we are creating the sequence on demand
                // we know the first id = 1 so initialize the
                // sequence at 2
                try {
                    $result = $this->conn->export->createSequence($seqName, 2);
                } catch(Doctrine_Exception $e) {
                    throw new Doctrine_Sequence_Exception('on demand sequence ' . $seqName . ' could not be created');
                }
                
                /**
                 * This could actually be a table that starts at 18.. oh well..
                 * we will keep the fallback to return 1 in case we skip this.. which
                 * should really not happen.. otherwise the returned values is biased.
                 */
                if ($this->checkSequence($seqName)) {
                    return $this->lastInsertId($seqName);
                }
                
                return 1;
            }
            throw $e;
        }
        
        $value = $this->lastInsertId($sequenceName);

        if (is_numeric($value)) {
            $query = 'DELETE FROM ' . $sequenceName . ' WHERE ' . $seqcolName . ' < ' . $value;
            
            try {
                $this->conn->exec($query);
            } catch (Doctrine_Connection_Exception $e) {
                throw new Doctrine_Sequence_Exception('Could not delete previous sequence from ' . $sequenceName . 
                                                      ' at ' . __FILE__ . ' in ' . __FUNCTION__ . ' with the message: ' .
                                                      $e->getMessage());
            }
        }
        return $value;
    }
    /**
     * Checks if there's a sequence that exists.
     *
     * @param  string $seqName     The sequence name to verify.
     * @return bool   $tableExists The value if the table exists or not
     * @access private
     */
    public function checkSequence($seqName)
    {
        $query = 'SELECT COUNT(1) FROM ' . $seqName;
        try {
            $this->conn->execute($query);
        } catch (Doctrine_Connection_Exception $e) {
            if ($e->getPortableCode() == Doctrine::ERR_NOSUCHTABLE) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param   string  name of the table into which a new row was inserted
     * @param   string  name of the field into which a new row was inserted
     */
    public function lastInsertId($table = null, $field = null)
    {
        $serverInfo = $this->conn->getServerVersion();
        if (is_array($serverInfo)
            && ! is_null($serverInfo['major'])
            && $serverInfo['major'] >= 8) {

            $query = 'SELECT SCOPE_IDENTITY()';

        } else {
            $query = 'SELECT @@IDENTITY';
        }

        return $this->conn->fetchOne($query);
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
        $this->warnings[] = 'database does not support getting current
            sequence value, the sequence value was incremented';
        return $this->nextId($seqName);
    }
}
