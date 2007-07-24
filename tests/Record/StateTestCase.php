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
class Doctrine_Record_State_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }
    public function prepareData() { }
    
    public function testAssignUnknownState() {
        $user = new User();
        try {
            $user->state(123123);
            $this->fail();
        } catch(Doctrine_Record_State_Exception $e) {
            $this->pass();
        }
        $this->assertEqual($user->state(), Doctrine_Record::STATE_TCLEAN);
        try {
            $user->state('some unknown state');
            $this->fail();
        } catch(Doctrine_Record_State_Exception $e) {
            $this->pass();
        }
        $this->assertEqual($user->state(), Doctrine_Record::STATE_TCLEAN);
    }

    public function testAssignDirtyState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_DIRTY);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_DIRTY);
        
        $user->state('dirty');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_DIRTY);
    }
    public function testAssignCleanState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_CLEAN);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_CLEAN);
        
        $user->state('clean');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_CLEAN);
    }
    public function testAssignTransientCleanState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_TCLEAN);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_TCLEAN);
        
        $user->state('tclean');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_TCLEAN);
    }
    public function testAssignTransientDirtyState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_TDIRTY);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_TDIRTY);
        
        $user->state('tdirty');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_TDIRTY);
    }
    public function testAssignProxyState() {
        $user = new User();

        $user->state(Doctrine_Record::STATE_PROXY);

        $this->assertEqual($user->state(), Doctrine_Record::STATE_PROXY);
        
        $user->state('proxy');

        $this->assertEqual($user->state(), Doctrine_Record::STATE_PROXY);
    }
    public function testProxiesAreAutomaticallyUpdatedWithFetches()
    {
        $user = new User();
        $user->name = 'someuser';
        $user->password = '123';
        $user->save();

        $this->connection->clear();

        $user = $this->connection->queryOne("SELECT u.name FROM User u WHERE u.name = 'someuser'");
        
        $this->assertEqual($user->state(), Doctrine_Record::STATE_PROXY);
        
        $user2 = $this->connection->queryOne("FROM User u WHERE u.name = 'someuser'");     

        $this->assertEqual($user->getOID(), $user2->getOID());
        
        $count = count($this->dbh);
        
        $this->assertEqual($user->password, '123');
        
        $this->assertEqual($count, count($this->dbh));
    }

    public function testAssignFieldsToProxies() {

        $user = new User();
        $user->name = 'someuser';
        $user->password = '123';
        $user->save();

        $this->connection->clear();

        $user = $this->connection->queryOne("SELECT u.name FROM User u WHERE u.name = 'someuser'");
        $user->name = 'someother';
        $user->save();
        $this->assertEqual($user->name, 'someother');
    	
    }
}
