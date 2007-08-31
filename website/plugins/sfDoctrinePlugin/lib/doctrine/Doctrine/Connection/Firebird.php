<?php
/*
 *  $Id: Firebird.php 1080 2007-02-10 18:17:08Z romanb $
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
Doctrine::autoload('Doctrine_Connection');
/**
 * Doctrine_Connection_Firebird
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Lorenzo Alberton <l.alberton@quipo.it> (PEAR MDB2 Interbase driver)
 * @version     $Revision: 1080 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Connection_Firebird extends Doctrine_Connection
{
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Firebird';
    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO $pdo                          database handle
     */
    public function __construct(Doctrine_Manager $manager, $adapter)
    {

        $this->supported = array(
                          'sequences'             => true,
                          'indexes'               => true,
                          'affected_rows'         => true,
                          'summary_functions'     => true,
                          'order_by_text'         => true,
                          'transactions'          => true,
                          'savepoints'            => true,
                          'current_id'            => true,
                          'limit_queries'         => 'emulated',
                          'LOBs'                  => true,
                          'replace'               => 'emulated',
                          'sub_selects'           => true,
                          'auto_increment'        => true,
                          'primary_key'           => true,
                          'result_introspection'  => true,
                          'prepared_statements'   => true,
                          'identifier_quoting'    => false,
                          'pattern_escaping'      => true
                          );
        // initialize all driver options
        /**
        $this->options['DBA_username'] = false;
        $this->options['DBA_password'] = false;
        $this->options['database_path'] = '';
        $this->options['database_extension'] = '.gdb';
        $this->options['server_version'] = '';
        */
        parent::__construct($manager, $adapter);
    }
    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     *
     * @return void
     */
    public function setCharset($charset)
    {
        $query = 'SET NAMES '.$this->dbh->quote($charset);
        $this->exec($query);
    }
    /**
     * Adds an driver-specific LIMIT clause to the query
     *
     * @param string $query     query to modify
     * @param integer $limit    limit the number of rows
     * @param integer $offset   start reading from given offset
     * @return string modified  query
     */
    public function modifyLimitQuery($query, $limit, $offset)
    {
        if ($limit > 0) {
            $query = preg_replace('/^([\s(])*SELECT(?!\s*FIRST\s*\d+)/i',
                "SELECT FIRST $limit SKIP $offset", $query);
        }
        return $query;
    }
}
