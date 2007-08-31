<?php
/*
 *  $Id: Oracle.php 1119 2007-02-17 18:21:26Z zYne $
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
Doctrine::autoload('Doctrine_Transaction');
/**
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1119 $
 */
class Doctrine_Transaction_Oracle extends Doctrine_Transaction
{
    /**
     * createSavepoint
     * creates a new savepoint
     *
     * @param string $savepoint     name of a savepoint to set
     * @return void
     */
    protected function createSavePoint($savepoint)
    {
        $query = 'SAVEPOINT ' . $savepoint;

        return $this->conn->execute($query);
    }
    /**
     * releaseSavePoint
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to release
     * @return void
     */
    protected function releaseSavePoint($savepoint)
    {
        // oracle doesn't support manual releasing of savepoints
        return true;
    }
    /**
     * rollbackSavePoint
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to rollback to
     * @return void
     */
    protected function rollbackSavePoint($savepoint)
    {
        $query = 'ROLLBACK TO SAVEPOINT ' . $savepoint;

        return $this->conn->execute($query);
    }
    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     * @throws PDOException                         if something fails at the PDO level
     * @throws Doctrine_Transaction_Exception       if using unknown isolation level
     * @return void
     */
    public function setIsolation($isolation)
    {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
                $isolation = 'READ COMMITTED';
                break;
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
                $isolation = 'SERIALIZABLE';
                break;
            default:
                throw new Doctrine_Transaction_Exception('Isolation level ' . $isolation . ' is not supported.');
        }

        $query = 'ALTER SESSION ISOLATION LEVEL ' . $isolation;
        return $this->conn->execute($query);
    }
}
