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

/**
 * Doctrine_UnitTestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_UnitTestCase extends UnitTestCase 
{
    protected $manager;
    protected $connection;
    protected $objTable;
    protected $new;
    protected $old;
    protected $dbh;
    protected $listener;

    protected $users;
    protected $valueHolder;
    protected $tables = array();
    protected $unitOfWork;
    protected $driverName = false;
    protected $generic = false;
    protected $conn;
    protected $adapter;
    protected $export;
    protected $expr;
    protected $dataDict;
    protected $transaction;


    protected $init = false;

    public function init() 
    {
        $name = get_class($this);

        $this->manager   = Doctrine_Manager::getInstance();
        $this->manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_ALL);

        $this->tables = array_merge($this->tables, 
                        array('entity',
                              'entityReference',
                              'email',
                              'phonenumber',
                              'groupuser',
                              'album',
                              'song',
                              'element',
                              'error',
                              'description',
                              'address',
                              'account',
                              'task',
                              'resource',
                              'assignment',
                              'resourceType',
                              'resourceReference')
                              );


        $class = get_class($this);
        $e     = explode('_', $class);


        if ( ! $this->driverName) {
            $this->driverName = 'main';
    
            switch($e[1]) {
                case 'Export':
                case 'Import':
                case 'Expression':
                case 'Transaction':
                case 'DataDict':
                case 'Sequence':
                    $this->driverName = 'Sqlite';
                break;
            }
    
            if(count($e) > 3) {
                $driver = $e[2];
                switch($e[2]) {
                    case 'Firebird':
                    case 'Informix':
                    case 'Mysql':
                    case 'Mssql':
                    case 'Oracle':
                    case 'Pgsql':
                    case 'Sqlite':
                        $this->driverName = $e[2];
                    break;
                }
            }
        }

        try {
            $this->conn = $this->connection = $this->manager->getConnection($this->driverName);
            $this->manager->setCurrentConnection($this->driverName);

            $this->connection->evictTables();
            $this->dbh      = $this->adapter = $this->connection->getDbh();
            $this->listener = $this->manager->getAttribute(Doctrine::ATTR_LISTENER);

            $this->manager->setAttribute(Doctrine::ATTR_LISTENER, $this->listener);

        } catch(Doctrine_Manager_Exception $e) {
            if($this->driverName == 'main') {
                $this->dbh = Doctrine_Db::getConnection('sqlite::memory:');
            } else {
                $this->dbh = $this->adapter = new Doctrine_Adapter_Mock($this->driverName);
            }

            $this->conn = $this->connection = $this->manager->openConnection($this->dbh, $this->driverName);

            if($this->driverName !== 'main') {
                $exc  = 'Doctrine_Connection_' . ucwords($this->driverName) . '_Exception';

                $this->exc = new $exc();

            } else {
            }

            $this->listener = new Doctrine_EventListener();
            $this->manager->setAttribute(Doctrine::ATTR_LISTENER, $this->listener);
        }
        if ($this->driverName !== 'main') {
            $this->export       = $this->connection->export;
            $this->transaction  = $this->connection->transaction;
            $this->dataDict     = $this->connection->dataDict;
            $this->expr         = $this->connection->expression;
            $this->sequence     = $this->connection->sequence;
            $this->import       = $this->connection->import;
        }
        $this->unitOfWork = $this->connection->unitOfWork;
        $this->connection->setListener(new Doctrine_EventListener());
        $this->query = new Doctrine_Query($this->connection);

        if ($this->driverName === 'main') {
            $this->prepareTables();
            $this->prepareData();
        }
    }
    public function prepareTables() {
        foreach($this->tables as $name) {
            $query = 'DROP TABLE ' . Doctrine::tableize($name);
            try {
                $this->dbh->query($query);
            } catch(PDOException $e) {

            }
        }

        $this->conn->export->exportClasses($this->tables);
        /**
        foreach($this->tables as $name) {
            $name = ucwords($name);
            $table = $this->connection->getTable($name);

            $table->clear();
        }
        */

        $this->objTable = $this->connection->getTable('User');
    }
    public function prepareData() 
    {
        $groups = new Doctrine_Collection($this->connection->getTable('Group'));

        $groups[0]->name = 'Drama Actors';

        $groups[1]->name = 'Quality Actors';


        $groups[2]->name = 'Action Actors';
        $groups[2]['Phonenumber'][0]->phonenumber = '123 123';
        $groups->save();

        $users = new Doctrine_Collection('User');


        $users[0]->name = 'zYne';
        $users[0]['Email']->address = 'zYne@example.com';
        $users[0]['Phonenumber'][0]->phonenumber = '123 123';

        $users[1]->name = 'Arnold Schwarzenegger';
        $users[1]->Email->address = 'arnold@example.com';
        $users[1]['Phonenumber'][0]->phonenumber = '123 123';
        $users[1]['Phonenumber'][1]->phonenumber = '456 456';
        $users[1]->Phonenumber[2]->phonenumber = '789 789';
        $users[1]->Group[0] = $groups[2];

        $users[2]->name = 'Michael Caine';
        $users[2]->Email->address = 'caine@example.com';
        $users[2]->Phonenumber[0]->phonenumber = '123 123';

        $users[3]->name = 'Takeshi Kitano';
        $users[3]->Email->address = 'kitano@example.com';
        $users[3]->Phonenumber[0]->phonenumber = '111 222 333';

        $users[4]->name = 'Sylvester Stallone';
        $users[4]->Email->address = 'stallone@example.com';
        $users[4]->Phonenumber[0]->phonenumber = '111 555 333';
        $users[4]['Phonenumber'][1]->phonenumber = '123 213';
        $users[4]['Phonenumber'][2]->phonenumber = '444 555';

        $users[5]->name = 'Kurt Russell';
        $users[5]->Email->address = 'russell@example.com';
        $users[5]->Phonenumber[0]->phonenumber = '111 222 333';

        $users[6]->name = 'Jean Reno';
        $users[6]->Email->address = 'reno@example.com';
        $users[6]->Phonenumber[0]->phonenumber = '111 222 333';
        $users[6]['Phonenumber'][1]->phonenumber = '222 123';
        $users[6]['Phonenumber'][2]->phonenumber = '123 456';

        $users[7]->name = 'Edward Furlong';
        $users[7]->Email->address = 'furlong@example.com';
        $users[7]->Phonenumber[0]->phonenumber = '111 567 333';

        $this->users = $users;
        $this->connection->flush();
    }
    public function getConnection() 
    {
        return $this->connection;
    }
    public function assertDeclarationType($type, $type2) 
    {
        $dec = $this->getDeclaration($type);
        
        if ( ! is_array($type2)) {
            $type2 = array($type2);
        }

        $this->assertEqual($dec['type'], $type2);
    }
    public function getDeclaration($type) 
    {
        return $this->dataDict->getPortableDeclaration(array('type' => $type, 'name' => 'colname', 'length' => 1, 'fixed' => true));
    }
    public function clearCache() 
    {
        foreach($this->tables as $name) {
            $table = $this->connection->getTable($name);
            $table->getCache()->deleteAll();
        }
    }
    public function setUp()
    {
        if ( ! $this->init) {
            $this->init();
        }
        if (isset($this->objTable)) {
            $this->objTable->clear();
        }
        
        $this->init    = true;
    }
}
?>
