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
 * Doctrine_Template_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Template_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareTables()
    { }
    public function prepareData() 
    { }

    public function testAccessingNonExistingImplementationThrowsException()
    {
        try {
            $user = new ConcreteUser();
            $user->Group;
            $this->fail();
        } catch (Doctrine_Relation_Parser_Exception $e) {
            $this->pass();
        }
    }
    
    public function testAccessingExistingImplementationSupportsAssociations()
    {
        $this->manager->setImpl('UserTemplate', 'ConcreteUser')
                      ->setImpl('GroupUserTemplate', 'ConcreteGroupUser')
                      ->setImpl('GroupTemplate', 'ConcreteGroup')
                      ->setImpl('EmailTemplate', 'ConcreteEmail');

        $user = new ConcreteUser();
        $group = $user->Group[0];

        $this->assertTrue($group instanceof ConcreteGroup);

        $this->assertTrue($group->User[0] instanceof ConcreteUser);
    }
    public function testAccessingExistingImplementationSupportsForeignKeyRelations()
    {

        $user = new ConcreteUser();

        $this->assertTrue($user->Email[0] instanceof ConcreteEmail);
    }

    public function testShouldCallMethodInTemplate()
    {
        $user = new ConcreteUser();
        $this->assertEqual("foo", $user->foo());
    }

}

// move these to ../templates?
class UserTemplate extends Doctrine_Template
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('password', 'string');
    }
    public function setUp()
    {
        $this->hasMany('GroupTemplate as Group', array('local' => 'user_id',
                                                       'foreign' => 'group_id',
                                                       'refClass' => 'GroupUserTemplate'));
        $this->hasMany('EmailTemplate as Email', array('local' => 'id',
                                                       'foreign' => 'user_id'));
    }
    
    public function foo()
    {
        return "foo";
    }
}
class EmailTemplate extends Doctrine_Template
{
    public function setTableDefinition()
    {
        $this->hasColumn('address', 'string');
        $this->hasColumn('user_id', 'integer');
    }
    public function setUp()
    {
        $this->hasOne('UserTemplate as User', array('local' => 'user_id',
                                                    'foreign' => 'id'));
    }
}
class GroupTemplate extends Doctrine_Template
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }
    public function setUp()
    {
        $this->hasMany('UserTemplate as User', array('local' => 'user_id',
                                                     'foreign' => 'group_id',
                                                     'refClass' => 'GroupUserTemplate'));
    }
}
class GroupUserTemplate extends Doctrine_Template
{
    public function setTableDefinition()
    {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('group_id', 'integer');
    }
}
