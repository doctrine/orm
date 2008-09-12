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

#namespace Doctrine::DBAL::Transactions;

/**
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @todo remove
 */
class Doctrine_Transaction_Firebird extends Doctrine_Transaction
{
    /**
     * creates a new savepoint
     *
     * @param string $savepoint     name of a savepoint to set
     * @return void
     * @override
     */
    protected function createSavePoint($savepoint)
    {
        $query = 'SAVEPOINT ' . $savepoint;

        return $this->conn->execute($query);
    }

    /**
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to release
     * @return void
     * @override
     */
    protected function releaseSavePoint($savepoint)
    {
        $query = 'RELEASE SAVEPOINT ' . $savepoint;

        return $this->conn->execute($query);
    }

    /**
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to rollback to
     * @return void
     * @override
     */
    protected function rollbackSavePoint($savepoint)
    {
        $query = 'ROLLBACK TO SAVEPOINT '.$savepoint;

        return $this->conn->execute($query);
    }

    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level (SQL-92)
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     *
     * @param   array some transaction options:
     *                  'wait' => 'WAIT' | 'NO WAIT'
     *                  'rw'   => 'READ WRITE' | 'READ ONLY'
     *
     * @throws PDOException                         if something fails at the PDO level
     * @throws Doctrine_Transaction_Exception       if using unknown isolation level or unknown wait option
     * @return void
     * @override
     */
    public function setIsolation($isolation, $options = array()) {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
                $nativeIsolation = 'READ COMMITTED RECORD_VERSION';
                break;
            case 'READ COMMITTED':
                $nativeIsolation = 'READ COMMITTED NO RECORD_VERSION';
                break;
            case 'REPEATABLE READ':
                $nativeIsolation = 'SNAPSHOT';
                break;
            case 'SERIALIZABLE':
                $nativeIsolation = 'SNAPSHOT TABLE STABILITY';
                break;
            default:
                throw new Doctrine_Transaction_Exception('isolation level is not supported: ' . $isolation);
        }

        $rw = $wait = '';

        if (isset($options['wait'])) {
            switch ($options['wait']) {
                case 'WAIT':
                case 'NO WAIT':
                    $wait = ' ' . $options['wait'];
                break;
                default:
                    throw new Doctrine_Transaction_Exception('wait option is not supported: ' . $options['wait']);
            }
        }

        if (isset($options['rw'])) {
            switch ($options['rw']) {
                case 'READ ONLY':
                case 'READ WRITE':
                    $rw = ' ' . $options['rw'];
                    break;
                default:
                    throw new Doctrine_Transaction_Exception('wait option is not supported: ' . $options['rw']);
            }
        }

        $query = 'SET TRANSACTION' . $rw . $wait .' ISOLATION LEVEL ' . $nativeIsolation;

        $this->conn->execute($query);
    }
}