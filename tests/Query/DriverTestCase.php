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
 * Doctrine_Query_Driver_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Driver_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData()
    { }
    public function prepareTables()
    { }

    public function testLimitQueriesForPgsql()
    {
    	$this->dbh = new Doctrine_Adapter_Mock('pgsql');

        $conn = $this->manager->openConnection($this->dbh);

        $q = new Doctrine_Query($conn);
    
        $q->from('User u')->limit(5);

        $this->assertEqual($q->getSql(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0) LIMIT 5');
    }

    public function testLimitQueriesForSqlite()
    {
    	$this->dbh = new Doctrine_Adapter_Mock('sqlite');

        $conn = $this->manager->openConnection($this->dbh);

        $q = new Doctrine_Query($conn);
    
        $q->from('User u')->limit(5);

        $this->assertEqual($q->getSql(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0) LIMIT 5');
    }
    
    public function testLimitQueriesForMysql()
    {
    	$this->dbh = new Doctrine_Adapter_Mock('mysql');

        $conn = $this->manager->openConnection($this->dbh);

        $q = new Doctrine_Query($conn);
    
        $q->from('User u')->limit(5);

        $this->assertEqual($q->getSql(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0) LIMIT 5');
    }

    public function testLimitQueriesForOracle()
    {
    	$this->dbh = new Doctrine_Adapter_Mock('oracle');

        $conn = $this->manager->openConnection($this->dbh);

        $q = new Doctrine_Query($conn);

        $q->from('User u')->limit(5);

        $this->assertEqual($q->getSql(), 'SELECT a.* FROM (SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)) a WHERE ROWNUM <= 5');
    }

    public function testLimitOffsetQueriesForOracle()
    {
    	$this->dbh = new Doctrine_Adapter_Mock('oracle');

        $conn = $this->manager->openConnection($this->dbh);

        $q = new Doctrine_Query($conn);

        $q->from('User u')->limit(5)->offset(2);

        $this->assertEqual($q->getSql(), 'SELECT * FROM (SELECT a.*, ROWNUM dctrn_rownum FROM (SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)) a WHERE ROWNUM <= 7) WHERE dctrn_rownum >= 3');
    }
}
