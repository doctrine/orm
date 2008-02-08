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
 * Doctrine_Import_Firebird_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Import_Firebird_TestCase extends Doctrine_UnitTestCase 
{
    public function testListTablesExecutesSql() 
    {
        $this->import->listTables();
        
        $this->assertEqual($this->adapter->pop(), 'SELECT RDB$RELATION_NAME FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG=0 AND RDB$VIEW_BLR IS NULL');
    }
    public function testListTableFieldsExecutesSql()
    {
        $this->import->listTableFields('table');
        
        $this->assertEqual($this->adapter->pop(), "SELECT RDB\$FIELD_NAME FROM RDB\$RELATION_FIELDS WHERE UPPER(RDB\$RELATION_NAME) = 'TABLE'");
    }
    public function testListUsersExecutesSql()
    {
        $this->import->listUsers();

        $this->assertEqual($this->adapter->pop(), "SELECT DISTINCT RDB\$USER FROM RDB\$USER_PRIVILEGES");
    }
    public function testListViewsExecutesSql()
    {
        $this->import->listViews();
        
        $this->assertEqual($this->adapter->pop(), "SELECT DISTINCT RDB\$VIEW_NAME FROM RDB\$VIEW_RELATIONS");
    }
    public function testListFunctionsExecutesSql()
    {
        $this->import->listFunctions('table');
        
        $this->assertEqual($this->adapter->pop(), "SELECT RDB\$FUNCTION_NAME FROM RDB\$FUNCTIONS WHERE RDB\$SYSTEM_FLAG IS NULL");
    }
    public function testListTableViewsExecutesSql()
    {
        $this->import->listTableViews('table');
        
        $this->assertEqual($this->adapter->pop(), "SELECT DISTINCT RDB\$VIEW_NAME FROM RDB\$VIEW_RELATIONS WHERE UPPER(RDB\$RELATION_NAME) = 'TABLE'");
    }
    public function testListTableTriggersExecutesSql()
    {
        $this->import->listTableTriggers('table');
        
        $this->assertEqual($this->adapter->pop(), "SELECT RDB\$TRIGGER_NAME FROM RDB\$TRIGGERS WHERE RDB\$SYSTEM_FLAG IS NULL OR RDB\$SYSTEM_FLAG = 0 WHERE UPPER(RDB\$RELATION_NAME) = 'TABLE'");
    }
}
