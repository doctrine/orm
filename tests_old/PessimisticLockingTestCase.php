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
 * Doctrine_Boolean_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_PessimisticLocking_TestCase extends Doctrine_UnitTestCase {
    private $lockingManager;

    /**
     * Sets up everything for the lock testing
     *
     * Creates a locking manager and a test record to work with.
     */
    public function testInitData() {
        $this->lockingManager = new Doctrine_Locking_Manager_Pessimistic($this->connection);
        
        // Create sample data to test on
        $entry1 = new Forum_Entry();
        $entry1->author = 'Bart Simpson';
        $entry1->topic  = 'I love donuts!';
        $entry1->save();
    }

    /**
     * Tests the basic locking mechanism
     * 
     * Currently tested: successful lock, failed lock, release lock 
     */
    public function testLock() {
        $entries = $this->connection->query("FROM Forum_Entry WHERE Forum_Entry.author = 'Bart Simpson'");
        
        // Test successful lock
        $gotLock = $this->lockingManager->getLock($entries[0], 'romanb');
        $this->assertTrue($gotLock);
        
        // Test failed lock (another user already got a lock on the entry)
        $gotLock = $this->lockingManager->getLock($entries[0], 'konstav');
        $this->assertFalse($gotLock);
        
        // Test release lock
        $released = $this->lockingManager->releaseLock($entries[0], 'romanb');
        $this->assertTrue($released);
    }

    /**
     * Tests the release mechanism of aged locks
     * This test implicitly tests getLock().
     */
    public function testReleaseAgedLocks() {
        $entries = $this->connection->query("FROM Forum_Entry WHERE Forum_Entry.author = 'Bart Simpson'");
        $this->lockingManager->getLock($entries[0], 'romanb');
        $released = $this->lockingManager->releaseAgedLocks(-1); // age -1 seconds => release all
        $this->assertEqual(1, $released);
        
        // A second call should return false (no locks left)
        $released = $this->lockingManager->releaseAgedLocks(-1);
        $this->assertEqual(0, $released);
        
        // Test with further parameters
        $this->lockingManager->getLock($entries[0], 'romanb');
        $released = $this->lockingManager->releaseAgedLocks(-1, 'User'); // shouldnt release anything
        $this->assertEqual(0, $released);
        $released = $this->lockingManager->releaseAgedLocks(-1, 'Forum_Entry'); // should release the lock
        $this->assertEqual(1, $released);
        
        $this->lockingManager->getLock($entries[0], 'romanb');
        $released = $this->lockingManager->releaseAgedLocks(-1, 'Forum_Entry', 'zyne'); // shouldnt release anything
        $this->assertEqual(0, $released);
        $released = $this->lockingManager->releaseAgedLocks(-1, 'Forum_Entry', 'romanb'); // should release the lock
        $this->assertEqual(1, $released);
    }

    /**
     * Tests the retrieving of a lock's owner.
     * This test implicitly tests getLock().
     *
     * @param Doctrine_Entity $lockedRecord
     */
    public function testGetLockOwner() {
        $entries = $this->connection->query("FROM Forum_Entry WHERE Forum_Entry.author = 'Bart Simpson'");
        $gotLock = $this->lockingManager->getLock($entries[0], 'romanb');
        $this->assertEqual('romanb', $this->lockingManager->getLockOwner($entries[0]));
    }
}

