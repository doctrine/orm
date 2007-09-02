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
 * Doctrine_Export_Firebird_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export_Firebird_TestCase extends Doctrine_UnitTestCase 
{
    public function testCreateDatabaseDoesNotExecuteSql() 
    {
        try {
            $this->export->createDatabase('db');
            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testDropDatabaseDoesNotExecuteSql() 
    {
        try {
            $this->export->dropDatabase('db');
            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testCreateTableSupportsAutoincPks() 
    {
        $name = 'mytable';
        
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true));

        $this->export->createTable($name, $fields);

        $this->assertEqual($this->adapter->pop(), 'CREATE TRIGGER mytable_AUTOINCREMENT_PK FOR mytable
                        ACTIVE BEFORE INSERT POSITION 0
                        AS
                        BEGIN
                        IF (NEW.id IS NULL OR NEW.id = 0) THEN
                            NEW.id = GEN_ID(mytable_seq, 1);
                        END');
    }
    public function testCreateTableSupportsDefaultAttribute() 
    {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10, 'default' => 'def'),
                         'type' => array('type' => 'integer', 'length' => 3, 'default' => 12)
                         );
                         
        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type INT DEFAULT 12, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsMultiplePks() 
    {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10),
                         'type' => array('type' => 'integer', 'length' => 3));
                         
        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);
        
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type INT, PRIMARY KEY(name, type))');
    }
}
