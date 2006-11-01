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
Doctrine::autoload("Doctrine_Connection_Common");
/**
 * Doctrine_Connection_Pgsql
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Connection_Pgsql extends Doctrine_Connection_Common {
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Pgsql';
    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO $pdo                          database handle
     */
    public function __construct(Doctrine_Manager $manager, PDO $pdo) {
        // initialize all driver options

        parent::__construct($manager, $pdo);
    }
    /**
     * returns the next value in the given sequence
     * @param string $sequence
     * @return integer
     */
    public function getNextID($sequence) {
        $stmt = $this->query("SELECT NEXTVAL('$sequence')");
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     * @return void
     */
    function setTransactionIsolation($isolation) {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
            break;
                throw new Doctrine_Connection_Pgsql_Exception('Isolation level '.$isolation.' is not supported.');
        }

        $query = 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL ' . $isolation;
        return $this->dbh->query($query);
    }

}

