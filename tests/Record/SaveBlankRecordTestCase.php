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
 * Doctrine_Record_State_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_SaveBlankRecord_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables[] = 'MyUserGroup';

        parent::prepareTables();
    }
    
    public function prepareData()
    { }

    public function testSaveBlankRecord()
    {
        $user = new User();
        $user->state('TDIRTY');
        $user->save();
        
        $this->assertTrue(isset($user['id']));
    }
    
    public function testSaveBlankRecord2()
    {
        $myUserGroup = new MyUserGroup();
        $myUserGroup->state('TDIRTY');
        $myUserGroup->save();
        
        $this->assertTrue(isset($user['id']));
    }
}

class MyUserGroup extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('my_user_group');
    
        $this->hasColumn('id', 'integer', 4, array (  'primary' => true,  'autoincrement' => true,));
        $this->hasColumn('group_id', 'integer', 4, array ());
        $this->hasColumn('user_id', 'integer', 4, array ());
    }
  
    public function setUp()
    {
        $this->hasOne('MyGroup as MyGroup', 'MyUserGroup.group_id');
        $this->hasOne('MyUser as MyUser', 'MyUserGroup.user_id');
    }
}

class MyGroup extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('my_group');

        $this->hasColumn('id', 'integer', 4, array (  'primary' => true,  'autoincrement' => true,));
        $this->hasColumn('name', 'string', 255, array (  'notnull' => true,));
        $this->hasColumn('description', 'string', 4000, array ());
    }

    public function setUp()
    {
        $this->hasMany('MyUser as users', array('refClass' => 'MyUserGroup', 'local' => 'group_id', 'foreign' => 'user_id'));
    } 
}

class MyUser2 extends Doctrine_Record
{  
    public function setTableDefinition()
    {
        $this->setTableName('my_user');

        $this->hasColumn('id', 'integer', 4, array (  'primary' => true,  'autoincrement' => true,));
        $this->hasColumn('username', 'string', 128, array (  'notnull' => true,));
        $this->hasColumn('algorithm', 'string', 128, array (  'default' => 'sha1',  'notnull' => true,));
        $this->hasColumn('salt', 'string', 128, array (  'notnull' => true,));
        $this->hasColumn('password', 'string', 128, array (  'notnull' => true,));
        $this->hasColumn('created_at', 'timestamp', null, array ());
        $this->hasColumn('last_login', 'timestamp', null, array ());
        $this->hasColumn('is_active', 'boolean', null, array (  'default' => 1,  'notnull' => true,));
        $this->hasColumn('is_super_admin', 'boolean', null, array (  'default' => 0,  'notnull' => true,));
    }

    public function setUp()
    {
        $this->hasMany('MyGroup as groups', array('refClass' => 'MyUserGroup', 'local' => 'user_id', 'foreign' => 'group_id'));
    }  
}