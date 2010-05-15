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

namespace Doctrine\DBAL\Driver\PDOMySql;

use Doctrine\DBAL\Connection;

/**
 * PDO MySql driver.
 *
 * @since 2.0
 */
class Driver implements \Doctrine\DBAL\Driver
{
    /**
     * Attempts to establish a connection with the underlying driver.
     *
     * @param array $params
     * @param string $username
     * @param string $password
     * @param array $driverOptions
     * @return Doctrine\DBAL\Driver\Connection
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        $conn = new \Doctrine\DBAL\Driver\PDOConnection(
            $this->_constructPdoDsn($params),
            $username,
            $password,
            $driverOptions
        );
        return $conn;
    }

    /**
     * Constructs the MySql PDO DSN.
     *
     * @return string  The DSN.
     */
    private function _constructPdoDsn(array $params)
    {
        $dsn = 'mysql:';
        if (isset($params['host'])) {
            $dsn .= 'host=' . $params['host'] . ';';
        }
        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }
        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }
        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }
        
        return $dsn;
    }

    public function getDatabasePlatform()
    {
        return new \Doctrine\DBAL\Platforms\MySqlPlatform();
    }

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new \Doctrine\DBAL\Schema\MySqlSchemaManager($conn);
    }

    public function getName()
    {
        return 'pdo_mysql';
    }

    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        $params = $conn->getParams();
        return $params['dbname'];
    }
}