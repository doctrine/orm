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

namespace Doctrine\DBAL\Driver\IBMDB2;

class DB2Connection implements \Doctrine\DBAL\Driver\Connection
{
    private $_conn = null;

    public function __construct(array $params, $username, $password, $driverOptions = array())
    {
        $isPersistant = (isset($params['persistent']) && $params['persistent'] == true);

        if ($isPersistant) {
            $this->_conn = db2_pconnect($params['dbname'], $username, $password, $driverOptions);
        } else {
            $this->_conn = db2_connect($params['dbname'], $username, $password, $driverOptions);
        }
        if (!$this->_conn) {
            throw new DB2Exception(db2_conn_errormsg());
        }
    }

    function prepare($sql)
    {
        $stmt = @db2_prepare($this->_conn, $sql);
        if (!$stmt) {
            throw new DB2Exception(db2_stmt_errormsg());
        }
        return new DB2Statement($stmt);
    }
    
    function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    function quote($input, $type=\PDO::PARAM_STR)
    {
        $input = db2_escape_string($input);
        if ($type == \PDO::PARAM_INT ) {
            return $input;
        } else {
            return "'".$input."'";
        }
    }

    function exec($statement)
    {
        $stmt = $this->prepare($statement);
        $stmt->execute();
        return $stmt->rowCount();
    }

    function lastInsertId($name = null)
    {
        return db2_last_insert_id($this->_conn);
    }

    function beginTransaction()
    {
        db2_autocommit($this->_conn, DB2_AUTOCOMMIT_OFF);
    }

    function commit()
    {
        if (!db2_commit($this->_conn)) {
            throw new DB2Exception(db2_conn_errormsg($this->_conn));
        }
        db2_autocommit($this->_conn, DB2_AUTOCOMMIT_ON);
    }

    function rollBack()
    {
        if (!db2_rollback($this->_conn)) {
            throw new DB2Exception(db2_conn_errormsg($this->_conn));
        }
        db2_autocommit($this->_conn, DB2_AUTOCOMMIT_ON);
    }

    function errorCode()
    {
        return db2_conn_error($this->_conn);
    }

    function errorInfo()
    {
        return array(
            0 => db2_conn_errormsg($this->_conn),
            1 => $this->errorCode(),
        );
    }
}