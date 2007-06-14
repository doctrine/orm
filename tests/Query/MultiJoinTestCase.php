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
 * Doctrine_Query_MultiJoin_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_MultiJoin_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareTables()
    {
        $this->tables[] = 'Book';
        $this->tables[] = 'Author';
        parent::prepareTables();
    }
    public function testInitializeData() 
    {

        $query = new Doctrine_Query($this->connection);

        $user = $this->connection->getTable('User')->find(4);


        $album = $this->connection->create('Album');
        $album->Song[0];

        $user->Album[0]->name = 'Damage Done';
        $user->Album[1]->name = 'Haven';

        $user->Album[0]->Song[0]->title = 'Damage Done';
        $user->Album[0]->Song[1]->title = 'The Treason Wall';
        $user->Album[0]->Song[2]->title = 'Monochromatic Stains';

        $this->assertEqual(count($user->Album[0]->Song), 3);


        $user->Album[1]->Song[0]->title = 'Not Built To Last';
        $user->Album[1]->Song[1]->title = 'The Wonders At Your Feet';
        $user->Album[1]->Song[2]->title = 'Feast Of Burden';
        $user->Album[1]->Song[3]->title = 'Fabric';
        $this->assertEqual(count($user->Album[1]->Song), 4);

        $user->save();

        $user = $this->objTable->find(4);

        $this->assertEqual(count($user->Album[0]->Song), 3);
        $this->assertEqual(count($user->Album[1]->Song), 4);
        
        
        $user = $this->connection->getTable('User')->find(5);
        
        $user->Album[0]->name = 'Clayman';
        $user->Album[1]->name = 'Colony';
        $user->Album[1]->Song[0]->title = 'Colony';
        $user->Album[1]->Song[1]->title = 'Ordinary Story';
        
        $user->save();
        
        $this->assertEqual(count($user->Album[0]->Song), 0);
        $this->assertEqual(count($user->Album[1]->Song), 2);
    }
    public function testMultipleOneToManyFetching() 
    {
        $this->connection->clear();

        $query = new Doctrine_Query();

        $users = $query->query('FROM User.Album.Song, User.Phonenumber WHERE User.id IN (4,5)');

        $this->assertEqual($users->count(), 2);

        $this->assertEqual($users[0]->id, 4);

        $this->assertEqual($users[0]->Album[0]->name, 'Damage Done');
        $this->assertEqual($users[0]->Album[0]->Song[0]->title, 'Damage Done');
        $this->assertEqual($users[0]->Album[0]->Song[1]->title, 'The Treason Wall');
        $this->assertEqual($users[0]->Album[0]->Song[2]->title, 'Monochromatic Stains');
        $this->assertEqual($users[0]->Album[1]->name, 'Haven');
        $this->assertEqual($users[0]->Album[1]->Song[0]->title, 'Not Built To Last');
        $this->assertEqual($users[0]->Album[1]->Song[1]->title, 'The Wonders At Your Feet');
        $this->assertEqual($users[0]->Album[1]->Song[2]->title, 'Feast Of Burden');
        $this->assertEqual($users[0]->Album[1]->Song[3]->title, 'Fabric');

        $this->assertEqual($users[1]->id, 5);
        $this->assertEqual($users[1]->Album[0]->name, 'Clayman');
        $this->assertEqual($users[1]->Album[1]->name, 'Colony');
        $this->assertEqual($users[1]->Album[1]->Song[0]->title, 'Colony');
        $this->assertEqual($users[1]->Album[1]->Song[1]->title, 'Ordinary Story');
        
        $this->assertEqual($users[0]->Phonenumber[0]->phonenumber, '123 123');
        
        $this->assertEqual($users[1]->Phonenumber[0]->phonenumber, '123 123');
        $this->assertEqual($users[1]->Phonenumber[1]->phonenumber, '456 456');
        $this->assertEqual($users[1]->Phonenumber[2]->phonenumber, '789 789');
    }

    public function testInitializeMoreData() 
    {
        $user = $this->connection->getTable('User')->find(4);
        $user->Book[0]->name = 'The Prince';
        $user->Book[0]->Author[0]->name = 'Niccolo Machiavelli';
        $user->Book[0]->Author[1]->name = 'Someone';
        $user->Book[1]->name = 'The Art of War';
        $user->Book[1]->Author[0]->name = 'Someone';
        $user->Book[1]->Author[1]->name = 'Niccolo Machiavelli';


        $user->save();

        $user = $this->connection->getTable('User')->find(5);
        $user->Book[0]->name = 'Zadig';
        $user->Book[0]->Author[0]->name = 'Voltaire';
        $user->Book[0]->Author[1]->name = 'Someone';
        $user->Book[1]->name = 'Candide';
        $user->Book[1]->Author[0]->name = 'Someone';
        $user->Book[1]->Author[1]->name = 'Voltaire';
        $user->save();

        $this->connection->clear();
    }
    public function testMultipleOneToManyFetching2() 
    {
        $query = new Doctrine_Query();

        $users = $query->query("FROM User.Album.Song, User.Book.Author WHERE User.id IN (4,5)");
        
        $this->assertEqual($users->count(), 2);

        $this->assertEqual($users[0]->id, 4);
        $this->assertEqual($users[0]->Album[0]->name, 'Damage Done');
        $this->assertEqual($users[0]->Album[0]->Song[0]->title, 'Damage Done');
        $this->assertEqual($users[0]->Album[0]->Song[1]->title, 'The Treason Wall');
        $this->assertEqual($users[0]->Album[0]->Song[2]->title, 'Monochromatic Stains');
        $this->assertEqual($users[0]->Album[1]->name, 'Haven');
        $this->assertEqual($users[0]->Album[1]->Song[0]->title, 'Not Built To Last');
        $this->assertEqual($users[0]->Album[1]->Song[1]->title, 'The Wonders At Your Feet');
        $this->assertEqual($users[0]->Album[1]->Song[2]->title, 'Feast Of Burden');
        $this->assertEqual($users[0]->Album[1]->Song[3]->title, 'Fabric');
        
        $this->assertEqual($users[0]->Book[0]->Author[0]->name, 'Niccolo Machiavelli');
        $this->assertEqual($users[0]->Book[0]->Author[1]->name, 'Someone');
        $this->assertEqual($users[0]->Book[1]->name, 'The Art of War');
        $this->assertEqual($users[0]->Book[1]->Author[0]->name, 'Someone');
        $this->assertEqual($users[0]->Book[1]->Author[1]->name, 'Niccolo Machiavelli');

        $this->assertEqual($users[1]->id, 5);
        $this->assertEqual($users[1]->Album[0]->name, 'Clayman');
        $this->assertEqual($users[1]->Album[1]->name, 'Colony');
        $this->assertEqual($users[1]->Album[1]->Song[0]->title, 'Colony');
        $this->assertEqual($users[1]->Album[1]->Song[1]->title, 'Ordinary Story');

        $this->assertEqual($users[1]->Book[0]->name, 'Zadig');
        $this->assertEqual($users[1]->Book[0]->Author[0]->name, 'Voltaire');
        $this->assertEqual($users[1]->Book[0]->Author[1]->name, 'Someone');
        $this->assertEqual($users[1]->Book[1]->name, 'Candide');
        $this->assertEqual($users[1]->Book[1]->Author[0]->name, 'Someone');
        $this->assertEqual($users[1]->Book[1]->Author[1]->name, 'Voltaire');
    }

    public function testMultipleOneToManyFetchingWithOrderBy() 
    {
        $query = new Doctrine_Query();

        $users = $query->query("FROM User.Album.Song WHERE User.id IN (4,5) ORDER BY User.Album.Song.title DESC");
    }
}
