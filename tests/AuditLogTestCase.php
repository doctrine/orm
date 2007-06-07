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
 * Doctrine_AuditLog_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_AuditLog_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    { }
    public function prepareTables()
    { }
    public function testVersionTableSqlReturnsProperQuery()
    {
        $table = $this->conn->getTable('Versionable');

        $auditLog = $table->getAuditLog();

        $auditLog->audit();

        $entity = new Versionable();
        $entity->name = 'zYne';
        $entity->save();
        $this->assertEqual($entity->version, 1);  

        $entity->name = 'zYne 2';
        $entity->save();

        $this->assertEqual($entity->version, 2);


        $entity->delete();
        $this->assertEqual($entity->version, 3);

        $entity->revert(2);

        $this->assertEqual($entity->name, 'zYne 2');
    }
    public function testUpdateTriggerSqlReturnsProperQuery()
    {
        $table = $this->conn->getTable('User');
        
        $auditLog = new Doctrine_AuditLog($table);
        
        $sql = $auditLog->updateTriggerSql($table);

        $this->assertEqual($sql, 'CREATE TRIGGER entity_dut UPDATE ON entity BEGIN INSERT INTO entity_dvt (id, name, loginname, password, type, created, updated, email_id) VALUES (old.id, old.name, old.loginname, old.password, old.type, old.created, old.updated, old.email_id); END;');
    }
    public function testDeleteTriggerSqlReturnsProperQuery()
    {
        $table = $this->conn->getTable('User');
        
        $auditLog = new Doctrine_AuditLog($table);
        
        $sql = $auditLog->deleteTriggerSql($table);

        $this->assertEqual($sql, 'CREATE TRIGGER entity_ddt BEFORE DELETE ON entity BEGIN INSERT INTO entity_dvt (id, name, loginname, password, type, created, updated, email_id) VALUES (old.id, old.name, old.loginname, old.password, old.type, old.created, old.updated, old.email_id); END;');
    }
}
class Versionable extends Doctrine_Record 
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('version', 'integer');
    }
    public function setUp()
    {

    }
}
