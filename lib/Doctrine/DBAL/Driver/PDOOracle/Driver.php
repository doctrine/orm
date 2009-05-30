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

namespace Doctrine\DBAL\Driver\PDOOracle;

use Doctrine\DBAL\Platforms;

class Driver implements \Doctrine\DBAL\Driver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new \Doctrine\DBAL\Driver\PDOConnection(
            $this->_constructPdoDsn($params),
            $username,
            $password,
            $driverOptions
        );
    }

    /**
     * Constructs the Oracle PDO DSN.
     *
     * @return string  The DSN.
     */
    private function _constructPdoDsn(array $params)
    {
        $dsn = 'oci:';
        if (isset($params['host'])) {
            $dsn .= 'dbname=(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)' .
                   '(HOST=' . $params['host'] . ')';

            if (isset($params['port'])) {
                $dsn .= '(PORT=' . $params['port'] . ')';
            } else {
                $dsn .= '(PORT=1521)';
            }

            $dsn .= '))(CONNECT_DATA=(SID=' . $params['dbname'] . ')))';
        } else {
            $dsn .= 'dbname=' . $params['dbname'];
        }

        if (isset($params['charset'])) {
            $dsn .= ';charset=' . $params['charset'];
        }

        return $dsn;
    }

    public function getDatabasePlatform()
    {
        return new \Doctrine\DBAL\Platforms\OraclePlatform();
    }

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new \Doctrine\DBAL\Schema\OracleSchemaManager($conn);
    }

    public function getName()
    {
        return 'pdo_oracle';
    }

    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        $params = $conn->getParams();
        return $params['user'];
    }
}