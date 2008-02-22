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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Relation_OneToOne_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Relation_OneToMany_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData()
    { }
    public function prepareTables()
    {
        $this->tables = array('Entity', 'Phonenumber', 'Email', 'Policy', 'PolicyAsset', 'Role', 'Auth');
        
        parent::prepareTables();
    }
    public function testRelationParsing()
    {
        $table = $this->conn->getClassMetadata('Entity');

        $rel = $table->getRelation('Phonenumber');

        $this->assertTrue($rel instanceof Doctrine_Relation_ForeignKey);

        $rel = $table->getRelation('Email');

        $this->assertTrue($rel instanceof Doctrine_Relation_LocalKey);
    }

    public function testRelationParsing2()
    {
        $table = $this->conn->getClassMetadata('Phonenumber');

        $rel = $table->getRelation('Entity');

        $this->assertTrue($rel instanceof Doctrine_Relation_LocalKey);
    }

    public function testRelationParsing3()
    {
        $table = $this->conn->getClassMetadata('Policy');

        $rel = $table->getRelation('PolicyAssets');

        $this->assertTrue($rel instanceof Doctrine_Relation_ForeignKey);
    }
    public function testRelationSaving() 
    {
        $p = new Policy();
        $p->policy_number = '123';
        
        $a = new PolicyAsset();
        $a->value = '123.13';

        $p->PolicyAssets[] = $a;
        $p->save();
        
        $this->assertEqual($a->policy_number, '123');
    }
    public function testRelationSaving2()
    {
        $e = new Entity();
        $e->name = 'test';
        $e->save();
         
        $nr = new Phonenumber();
        $nr->phonenumber = '1234556';
        $nr->save();
        $nr->Entity = $e;
    }
    public function testRelationSaving3() 
    {
        // create roles and user with role1 and role2
        $this->conn->beginTransaction();
        $role = new Role();
        $role->name = 'role1';
        $role->save();
     
        $auth = new Auth();
        $auth->name = 'auth1';
        $auth->Role = $role;
        $auth->save();
        
        $this->conn->commit();
     
        $this->conn->clear();

        $auths = $this->conn->query('FROM Auth a LEFT JOIN a.Role r');

        $this->assertEqual($auths[0]->Role->name, 'role1'); 
    }
}
