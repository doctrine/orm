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

namespace Doctrine\DBAL\Driver\IBMDB2;

use Doctrine\DBAL\Driver,
    Doctrine\DBAL\Connection;

/**
 * IBM DB2 Driver
 *
 * @since 2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DB2Driver implements Driver
{
    /**
     * Attempts to create a connection with the database.
     *
     * @param array $params All connection parameters passed by the user.
     * @param string $username The username to use when connecting.
     * @param string $password The password to use when connecting.
     * @param array $driverOptions The driver options to use when connecting.
     * @return Doctrine\DBAL\Driver\Connection The database connection.
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        if ( !isset($params['schema']) ) {
            
        }

        if ($params['host'] !== 'localhost' && $params['host'] != '127.0.0.1') {
            // if the host isn't localhost, use extended connection params
            $params['dbname'] = 'DRIVER={IBM DB2 ODBC DRIVER}' .
                     ';DATABASE=' . $params['dbname'] .
                     ';HOSTNAME=' . $params['host'] .
                     ';PORT='     . $params['port'] .
                     ';PROTOCOL=' . $params['protocol'] .
                     ';UID='      . $username .
                     ';PWD='      . $password .';';
            $username = null;
            $password = null;
        }

        return new DB2Connection($params, $username, $password, $driverOptions);
    }

    /**
     * Gets the DatabasePlatform instance that provides all the metadata about
     * the platform this driver connects to.
     *
     * @return Doctrine\DBAL\Platforms\AbstractPlatform The database platform.
     */
    public function getDatabasePlatform()
    {
        return new \Doctrine\DBAL\Platforms\DB2Platform;
    }

    /**
     * Gets the SchemaManager that can be used to inspect and change the underlying
     * database schema of the platform this driver connects to.
     *
     * @param  Doctrine\DBAL\Connection $conn
     * @return Doctrine\DBAL\SchemaManager
     */
    public function getSchemaManager(Connection $conn)
    {
        return new \Doctrine\DBAL\Schema\DB2SchemaManager($conn);
    }

    /**
     * Gets the name of the driver.
     *
     * @return string The name of the driver.
     */
    public function getName()
    {
        return 'ibm_db2';
    }

    /**
     * Get the name of the database connected to for this driver.
     *
     * @param  Doctrine\DBAL\Connection $conn
     * @return string $database
     */
    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        $params = $conn->getParams();
        return $params['dbname'];
    }
}
