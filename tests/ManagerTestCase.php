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
 * Doctrine_Manager_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Manager_TestCase extends Doctrine_UnitTestCase {
    public function testGetInstance() {
        $this->assertTrue(Doctrine_Manager::getInstance() instanceOf Doctrine_Manager);
    }
    public function testOpenConnection() {
        $this->assertTrue($this->connection instanceOf Doctrine_Connection);
    }
    public function testGetIterator() {
        $this->assertTrue($this->manager->getIterator() instanceof ArrayIterator);
    }
    public function testCount() {
        $this->assertTrue(is_integer(count($this->manager)));
    }
    public function testGetCurrentConnection() {
        $this->assertTrue($this->manager->getCurrentConnection() === $this->connection);
    }
    public function testGetConnections() {
        $this->assertTrue(is_integer(count($this->manager->getConnections())));
    }
    public function testClassifyTableize() {
        $name = "Forum_Category";
        $this->assertEqual(Doctrine::tableize($name), "forum__category");
        $this->assertEqual(Doctrine::classify(Doctrine::tableize($name)), $name);
        
        
    }
    public function testDsnParser()
    {
        $mysql = 'mysql://user:pass@localhost/dbname';
        
        // This is what is specified in the manul
        // I think it should be this for parse_url() to work
        // sqlite://full/unix/path/to/file.db
        // It expects only // since it thinks it is parsing a url
        // The problem after that is that the dns is not valid when being passed to PDO
        $sqlite = 'sqlite:/full/unix/path/to/file.db';
        $sqlitewin = 'sqlite:c:/full/windows/path/to/file.db';
        
        $manager = Doctrine_Manager::getInstance();
        
        try {
            $res = $manager->parseDsn($mysql);
            $expectedMysqlDsn = array(
                "scheme" => "mysql",
                "host" => "localhost",
                "user" => "user",
                "pass" => "pass",
                "path" => "/dbname",
                "dsn" => "mysql:host=localhost;dbname=dbname",
                "port" => NULL,
                "query" => NULL, 
                "fragment" => NULL,
                "database" => "dbname");
            $this->assertEqual($expectedMysqlDsn, $res);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
        
        try {
            $expectedDsn = array(
                "scheme" => "sqlite",
                "host" => null,
                "user" => null,
                "pass" => null,
                "path" => "/full/unix/path/to/file.db",
                "dsn" => "sqlite:/full/unix/path/to/file.db",
                "port" => NULL,
                "query" => NULL, 
                "fragment" => NULL,
                "database" => "/full/unix/path/to/file.db");
              
            $res = $manager->parseDsn($sqlite);
            $this->assertEqual($expectedDsn, $res);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
        
        try {
             $expectedDsn = array(
                "scheme" => "sqlite",
                "path" => "c:/full/windows/path/to/file.db",
                "dsn" => "sqlite:c:/full/windows/path/to/file.db",
                "host" => null,
                "port" => NULL,
                "user" => null,
                "pass" => null,
                "query" => NULL, 
                "fragment" => NULL,
                "database" => "c:/full/windows/path/to/file.db");
            $res = $manager->parseDsn($sqlitewin);
            $this->assertEqual($expectedDsn, $res);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }
    public function prepareData() { }
    public function prepareTables() { }
}
