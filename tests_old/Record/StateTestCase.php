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
 * Doctrine_Record_State_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_State_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareTables() 
    { 
        $this->tables = array('Entity');
        
        parent::prepareTables();
    }

    public function prepareData() 
    { }

    public function testAssigningAutoincId()
    {
        $user = new User();
        
        $this->assertEqual($user->id, null);
        
        $user->name = 'zYne';

        $user->save();

        $this->assertEqual($user->id, 1);
        
        $user->id = 2;

        $this->assertEqual($user->id, 2);
        
        $user->save();
    }

    /**
    public function testAssignFieldsToProxies()
    {
        $user = new User();
        $user->name = 'someuser';
        $user->password = '123';
        $user->save();

        $this->connection->clear();

        $user = $this->connection->queryOne("SELECT u.name FROM User u WHERE u.name = 'someuser'");
        $this->assertEqual($user->name, 'someuser');

        $user->name = 'someother';
        $this->assertEqual($user->name, 'someother');

        $user->save();
        $this->assertEqual($user->name, 'someother');
    }

    public function testAssignUnknownState() 
    {
        $user = new User();
        try {
            $user->state(123123);
            $this->fail();
        } catch(Doctrine_Record_State_Exception $e) {
            $this->pass();
        }
        $this->assertEqual($user->state(), Doctrine_Entity::STATE_TCLEAN);
        try {
            $user->state('some unknown state');
            $this->fail();
        } catch(Doctrine_Entity_State_Exception $e) {
            $this->pass();
        }
        $this->assertEqual($user->state(), Doctrine_Entity::STATE_TCLEAN);
    }

    public function testAssignDirtyState() 
    {
        $user = new User();

        $user->state(Doctrine_Entity::STATE_DIRTY);

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_DIRTY);
        
        $user->state('dirty');

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_DIRTY);
    }

    public function testAssignCleanState() 
    {
        $user = new User();

        $user->state(Doctrine_Entity::STATE_CLEAN);

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_CLEAN);
        
        $user->state('clean');

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_CLEAN);
    }

    public function testAssignTransientCleanState() 
    {
        $user = new User();

        $user->state(Doctrine_Entity::STATE_TCLEAN);

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_TCLEAN);
        
        $user->state('tclean');

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_TCLEAN);
    }

    public function testAssignTransientDirtyState() 
    {
        $user = new User();

        $user->state(Doctrine_Entity::STATE_TDIRTY);

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_TDIRTY);
        
        $user->state('tdirty');

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_TDIRTY);
    }

    public function testAssignProxyState()
    {
        $user = new User();

        $user->state(Doctrine_Entity::STATE_PROXY);

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_PROXY);
        
        $user->state('proxy');

        $this->assertEqual($user->state(), Doctrine_Entity::STATE_PROXY);
    }

    public function testProxiesAreAutomaticallyUpdatedWithFetches()
    {
        $user = new User();
        $user->name = 'someuser';
        $user->password = '123';
        $user->save();

        $this->connection->clear();

        $user = $this->connection->queryOne("SELECT u.name FROM User u WHERE u.name = 'someuser'");
        
        $this->assertEqual($user->state(), Doctrine_Entity::STATE_PROXY);
        
        $user2 = $this->connection->queryOne("FROM User u WHERE u.name = 'someuser'");     

        $this->assertEqual($user->getOID(), $user2->getOID());
        
        $count = count($this->dbh);
        
        $this->assertEqual($user->password, '123');
        
        $this->assertEqual($count, count($this->dbh));
    }
    
    public function testProxyToDirtyToProxy() {
        
        define('UNAME','someuser') ;
        define('UPWD1','123') ;
        define('UPWD2','456') ;
        define('ULNAME','somelogin') ;
        
        $user = new User() ;
        $user->name      = UNAME ;
        $user->password  = UPWD1 ;
        $user->loginname = ULNAME ;
        $user->save() ;
        
        $this->assertEqual($user->name,UNAME) ;
        $this->assertEqual($user->password,UPWD1) ;
        $this->assertEqual($user->loginname,ULNAME) ;
        
        // to make sure it is saved correctly
        $user1 = $this->connection->queryOne("FROM User u WHERE u.name = '" . UNAME . "'");
        $this->assertEqual($user1->state(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($user1->name,UNAME) ;
        $this->assertEqual($user1->password,UPWD1) ;
        $this->assertEqual($user1->loginname,ULNAME) ;
        
        $this->connection->clear() ;
        //$this->clearCache() ;
        
        // now lets fetch partially the object
        //$users = Doctrine_Query::create($this->connection)->select('u.name')->from('User u')->where("u.name='someuser'")->execute() ;
        //$user2 = $users[0] ;
        $user2 = $this->connection->queryOne("SELECT u.name FROM User u WHERE u.name = '" . UNAME . "'");
        
        // the object should be in state proxy with only 'name' fetched ...
        $this->assertEqual($user2->state(), Doctrine_Entity::STATE_PROXY);
        $this->assertEqual($user2->name,UNAME) ;
        $this->assertEqual($user2->password,null) ;
        $this->assertEqual($user2->loginname,null) ;
        
        // lets edit the object
        $user2->password = UPWD2 ;
        
        // now it should be dirty (but may be PDIRTY ... ?)
        $this->assertEqual($user2->state(),Doctrine_Entity::STATE_DIRTY) ;
        $this->assertEqual($user2->name,UNAME) ;
        $this->assertEqual($user2->password,UPWD2) ;
        $this->assertEqual($user2->loginname,null) ;
                
        // lets save
        $user2->save() ;

        // the logic would suggest the object to go back to PROXY mode (becausse $user2->loginname is null aka not sync with DB)
        $boolState = ($user2->loginname == null) && ($user2->state() === Doctrine_Entity::STATE_PROXY) ;
        // this one will currently fail 
        $this->assertTrue($boolState) ;
        // this will also currently fail (becausse it currently goes back to STATE_CLEAN, which shouldnt be the case)
        //$this->assertEqual($user2->state(), Doctrine_Entity::STATE_PROXY);
        $this->assertEqual($user2->name,UNAME) ;
        $this->assertEqual($user2->password,UPWD2) ;
        $this->assertEqual($user2->loginname,null) ;
     }
     */
}
