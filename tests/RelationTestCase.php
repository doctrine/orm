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
 * Doctrine_Relation_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Relation_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    { }
    public function prepareTables() 
    {
        $this->tables = array('RelationTest', 'RelationTestChild', 'Group', 'Groupuser', 'User', 'Email', 'Account', 'Phonenumber');
        
        parent::prepareTables();
    }

    public function testInitData() 
    {
        $user = new User();
        
        $user->name = 'zYne';
        $user->Group[0]->name = 'Some Group';
        $user->Group[1]->name = 'Other Group';
        $user->Group[2]->name = 'Third Group';
        
        $user->Phonenumber[0]->phonenumber = '123 123';
        $user->Phonenumber[1]->phonenumber = '234 234';
        $user->Phonenumber[2]->phonenumber = '456 456';
        
        $user->Email->address = 'someone@some.where';

        $user->save();
    }
    
    public function testUnlinkSupportsManyToManyRelations()
    {
        $users = Doctrine_Query::create()->from('User u')->where('u.name = ?', array('zYne'))->execute();
        
        $user = $users[0];
        
        $this->assertEqual($user->Group->count(), 3);
        
        $user->unlink('Group', array(2, 3, 4));
        
        $this->assertEqual($user->Group->count(), 0);
        
        $this->conn->clear();
        
        $groups = Doctrine_Query::create()->from('Group g')->execute();

        $this->assertEqual($groups->count(), 3);

        $links = Doctrine_Query::create()->from('GroupUser gu')->execute();

        $this->assertEqual($links->count(), 0);
    }

    public function testUnlinkSupportsOneToManyRelations()
    {
        $this->conn->clear();

        $users = Doctrine_Query::create()->from('User u')->where('u.name = ?', array('zYne'))->execute();
        
        $user = $users[0];
        
        $this->assertEqual($user->Phonenumber->count(), 3);
        
        $user->unlink('Phonenumber', array(1, 2, 3));
        
        $this->assertEqual($user->Phonenumber->count(), 0);
        
        $this->conn->clear();
        
        $phonenumber = Doctrine_Query::create()->from('Phonenumber p')->execute();

        $this->assertEqual($phonenumber->count(), 3);
        $this->assertEqual($phonenumber[0]->entity_id, null);
        $this->assertEqual($phonenumber[1]->entity_id, null);
        $this->assertEqual($phonenumber[2]->entity_id, null);  
    }

    public function testOneToManyTreeRelationWithConcreteInheritance() {

        $component = new RelationTestChild();

        try {
            $rel = $component->getTable()->getRelation('Children');

            $this->pass();
        } catch(Doctrine_Exception $e) {

            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_ForeignKey);

        $this->assertTrue($component->Children instanceof Doctrine_Collection);
        $this->assertTrue($component->Children[0] instanceof RelationTestChild);
    }

    public function testOneToOneTreeRelationWithConcreteInheritance() {
        $component = new RelationTestChild();
        
        try {
            $rel = $component->getTable()->getRelation('Parent');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_LocalKey);
    }
    public function testManyToManyRelation() {
        $user = new User();
         
        // test that join table relations can be initialized even before the association have been initialized
        try {
            $user->Groupuser;
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        //$this->assertTrue($user->getTable()->getRelation('Groupuser') instanceof Doctrine_Relation_ForeignKey);
        $this->assertTrue($user->getTable()->getRelation('Group') instanceof Doctrine_Relation_Association);
    }
    public function testOneToOneLocalKeyRelation() {
        $user = new User();
        
        $this->assertTrue($user->getTable()->getRelation('Email') instanceof Doctrine_Relation_LocalKey);
    }
    public function testOneToOneForeignKeyRelation() {
        $user = new User();
        
        $this->assertTrue($user->getTable()->getRelation('Account') instanceof Doctrine_Relation_ForeignKey);
    }
    public function testOneToManyForeignKeyRelation() {
        $user = new User();
        
        $this->assertTrue($user->getTable()->getRelation('Phonenumber') instanceof Doctrine_Relation_ForeignKey);
    }
}
