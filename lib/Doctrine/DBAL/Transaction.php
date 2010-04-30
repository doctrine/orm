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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL;

/**
 * The Transaction class is the central access point to DBAL Transaction functionality.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Transaction
{
    /**
     * Constant for transaction isolation level READ UNCOMMITTED.
     */
    const READ_UNCOMMITTED = 1;

    /**
     * Constant for transaction isolation level READ COMMITTED.
     */
    const READ_COMMITTED = 2;

    /**
     * Constant for transaction isolation level REPEATABLE READ.
     */
    const REPEATABLE_READ = 3;

    /**
     * Constant for transaction isolation level SERIALIZABLE.
     */
    const SERIALIZABLE = 4;

    /**
     * The transaction nesting level.
     *
     * @var integer
     */
    private $_transactionNestingLevel = 0;

    /**
     * The currently active transaction isolation level.
     *
     * @var integer
     */
    private $_transactionIsolationLevel;

    /**
     * Flag that indicates whether the current transaction is marked for rollback only.
     *
     * @var boolean
     */
    private $_isRollbackOnly = false;

    /**
     * Constructor
     *
     * @param Connection $conn The DBAL Connection
     */
    public function __construct(Connection $conn)
    {
        $this->_conn = $conn;

        $this->_transactionIsolationLevel = $conn->getDatabasePlatform()->getDefaultTransactionIsolationLevel();
    }

    /**
     * Checks whether a transaction is currently active.
     *
     * @return boolean TRUE if a transaction is currently active, FALSE otherwise.
     */
    public function isTransactionActive()
    {
        return $this->_transactionNestingLevel > 0;
    }

    /**
     * Sets the transaction isolation level.
     *
     * @param integer $level The level to set.
     */
    public function setTransactionIsolation($level)
    {
        $this->_transactionIsolationLevel = $level;

        return $this->executeUpdate($this->_conn->getDatabasePlatform()->getSetTransactionIsolationSQL($level));
    }

    /**
     * Gets the currently active transaction isolation level.
     *
     * @return integer The current transaction isolation level.
     */
    public function getTransactionIsolation()
    {
        return $this->_transactionIsolationLevel;
    }

    /**
     * Returns the current transaction nesting level.
     *
     * @return integer The nesting level. A value of 0 means there's no active transaction.
     */
    public function getTransactionNestingLevel()
    {
        return $this->_transactionNestingLevel;
    }

    /**
     * Starts a transaction by suspending auto-commit mode.
     *
     * @return void
     */
    public function begin()
    {
        $conn = $this->_conn->getWrappedConnection();

        if ($this->_transactionNestingLevel == 0) {
            $conn->beginTransaction();
        }

        ++$this->_transactionNestingLevel;
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     * @throws ConnectionException If the commit failed due to no active transaction or
     *                             because the transaction was marked for rollback only.
     */
    public function commit()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::commitFailedNoActiveTransaction();
        }
        
        if ($this->_isRollbackOnly) {
            throw ConnectionException::commitFailedRollbackOnly();
        }

        $conn = $this->_conn->getWrappedConnection();

        if ($this->_transactionNestingLevel == 1) {
            $conn->commit();
        }

        --$this->_transactionNestingLevel;
    }

    /**
     * Cancel any database changes done during the current transaction.
     *
     * this method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @throws ConnectionException If the rollback operation failed.
     */
    public function rollback()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::rollbackFailedNoActiveTransaction();
        }

        if ($this->_transactionNestingLevel == 1) {
            $this->_transactionNestingLevel = 0;
            $this->_conn->getWrappedConnection()->rollback();
            $this->_isRollbackOnly = false;
        } else {
            $this->_isRollbackOnly = true;
            --$this->_transactionNestingLevel;
        }
    }

    /**
     * Marks the current transaction so that the only possible
     * outcome for the transaction to be rolled back.
     *
     * @throws ConnectionException If no transaction is active.
     */
    public function setRollbackOnly()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::noActiveTransaction();
        }

        $this->_isRollbackOnly = true;
    }

    /**
     * Check whether the current transaction is marked for rollback only.
     *
     * @return boolean
     * @throws ConnectionException If no transaction is active.
     */
    public function getRollbackOnly()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::noActiveTransaction();
        }

        return $this->_isRollbackOnly;
    }
}