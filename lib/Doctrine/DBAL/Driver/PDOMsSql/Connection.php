<?php
/*
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

namespace Doctrine\DBAL\Driver\PDOMsSql;

use PDO, Doctrine\DBAL\Driver\Connection as DriverConnection;

/**
 * MsSql Connection implementation.
 *
 * @since 2.0
 */
class Connection extends PDO implements DriverConnection
{
    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $this->exec('ROLLBACK TRANSACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->exec('COMMIT TRANSACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->exec('BEGIN TRANSACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        $stmt = $this->query('SELECT SCOPE_IDENTITY()');
        $id = $stmt->fetchColumn();
        $stmt->closeCursor();
        return $id;
    }
}