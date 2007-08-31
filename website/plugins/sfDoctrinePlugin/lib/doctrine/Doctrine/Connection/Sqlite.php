<?php
/*
 *  $Id: Sqlite.php 2271 2007-08-25 00:14:14Z jackbravo $
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
 * Doctrine_Connection_Sqlite
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision: 2271 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Connection_Sqlite extends Doctrine_Connection_Common
{
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName = 'Sqlite';
    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager
     * @param PDO $pdo                          database handle
     */
    public function __construct(Doctrine_Manager $manager, $adapter)
    {

        $this->supported = array(
                          'sequences'            => 'emulated',
                          'indexes'              => true,
                          'affected_rows'        => true,
                          'summary_functions'    => true,
                          'order_by_text'        => true,
                          'current_id'           => 'emulated',
                          'limit_queries'        => true,
                          'LOBs'                 => true,
                          'replace'              => true,
                          'transactions'         => true,
                          'savepoints'           => false,
                          'sub_selects'          => true,
                          'auto_increment'       => true,
                          'primary_key'          => true,
                          'result_introspection' => false, // not implemented
                          'prepared_statements'  => 'emulated',
                          'identifier_quoting'   => true,
                          'pattern_escaping'     => false,
                          );
        /**
        $this->options['base_transaction_name'] = '___php_Doctrine_sqlite_auto_commit_off';
        $this->options['fixed_float'] = 0;
        $this->options['database_path'] = '';
        $this->options['database_extension'] = '';
        $this->options['server_version'] = '';
        */
        parent::__construct($manager, $adapter);
    }
    /**
     * initializes database functions missing in sqlite
     *
     * @see Doctrine_Expression
     * @return void
     */
    public function connect() 
    {
        parent::connect();

        $this->dbh->sqliteCreateFunction('mod',    array('Doctrine_Expression_Sqlite', 'modImpl'), 2);
        $this->dbh->sqliteCreateFunction('concat', array('Doctrine_Expression_Sqlite', 'concatImpl'));
        $this->dbh->sqliteCreateFunction('md5', 'md5', 1);
        $this->dbh->sqliteCreateFunction('sha1', 'sha1', 1);
        $this->dbh->sqliteCreateFunction('locate', 'strpos', 2);
        $this->dbh->sqliteCreateFunction('rtrim', 'rtrim', 1);
        $this->dbh->sqliteCreateFunction('ltrim', 'ltrim', 1);
        $this->dbh->sqliteCreateFunction('trim', 'trim', 1);
        $this->dbh->sqliteCreateFunction('now', 'time', 0);
    }
    /**
     * getDatabaseFile
     *
     * @param string $name      the name of the database
     * @return string
     */
    public function getDatabaseFile($name)
    {
        return $name . '.db';
    }
}
