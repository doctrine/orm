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
 * Doctrine_Migration_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Migration_TestCase extends Doctrine_UnitTestCase 
{
    public function testMigration()
    {
        // Upgrade one at a time
        Doctrine_Migration::migration(1, 2);
        Doctrine_Migration::migration(2, 3);
        Doctrine_Migration::migration(3, 4);
        
        // Then revert back to version 1
        Doctrine_Migration::migration(4, 1);
        
        // Check to make sure the current version is 1
        $this->assertEqual(Doctrine_Migration::getCurrentVersion(), 1);
    }
}

class MigrationTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        $this->hasColumn('field1', 'string');
    }
}

class Migration2 extends Doctrine_Migration
{
    public function up()
    {
        $this->createTable('migration_test', array('field1' => array('type' => 'string')));
    }
    
    public function down()
    {
        $this->dropTable('migration_test');
    }
}

class Migration3 extends Doctrine_Migration
{
    public function up()
    {
        $this->addColumn('migration_test', 'field1', 'string');
    }
    
    public function down()
    {
        $this->renameColumn('migration_test', 'field1', 'field2');
    }
}

class Migration4 extends Doctrine_Migration
{
    public function up()
    {
        $this->changeColumn('migration_test', 'field1', 'integer');
    }
    
    public function down()
    {
        $this->changeColumn('migration_test', 'field1', 'string');
    }  
}