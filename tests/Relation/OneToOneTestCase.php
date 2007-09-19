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
 * Doctrine_Relation_OneToOne_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Relation_OneToOne_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    { }
    public function prepareTables() 
    { 
        $this->tables = array('gnatUser','Email','Entity','Record_City', 'Record_Country', 'SelfRefTest');
        
        parent::prepareTables();
    }
    
     public function testOneToOneAggregateRelationWithAliasesIsSupported() 
    {
        $city = new Record_City();
        $country = $city->Country;

        $this->assertTrue($country instanceof Record_Country);  
    }
    
    public function testSelfReferentialOneToOneRelationsAreSupported()
    {
        $ref = new SelfRefTest();
        
        $rel = $ref->getTable()->getRelation('createdBy');

        $this->assertEqual($rel->getForeign(), 'id');
        $this->assertEqual($rel->getLocal(), 'created_by');
        
        $ref->name = 'ref 1';
        $ref->createdBy->name = 'ref 2';
        
        $ref->save();
    }
    public function testSelfReferentialOneToOneRelationsAreSupported2()
    {
        $this->connection->clear();
        
        $ref = $this->conn->queryOne("FROM SelfRefTest s WHERE s.name = 'ref 1'");
        $this->assertEqual($ref->name, 'ref 1');
        $this->assertEqual($ref->createdBy->name, 'ref 2');
    }

    public function testUnsetRelation()
    {
        $user = new User();
        $user->name = "test";
        $email = new Email();
        $email->address = "test@test.com";
        $user->Email = $email;
        $user->save();
        $this->assertTrue($user->Email instanceOf Email);
        $user->Email = Email::getNullObject();
        $user->save();
        $this->assertTrue($user->Email instanceOf Doctrine_Null);
    }
    
    public function testSavingRelatedObjects()
    {
        $user = new gnatUser();
        $user->name = "test";
        $email = new Email();
        $email->address = "test@test.com";
        $user->Email = $email;
        $user->save();
        $this->assertTrue($user->Email instanceOf Email);
        $this->assertTrue($user->email_id != 0);
        $this->assertTrue($user->email_id != null);
        $this->assertTrue($user->email_id == $user->Email->id);
        
    }
}
