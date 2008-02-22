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
class Doctrine_Record_Synchronize_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        parent::prepareTables();
    }
    
    public function prepareData()
    {
        $user = new User();
        $user->name = 'John';
        $user->Email->address = 'john@mail.com';
        $user->Phonenumber[0]->phonenumber = '555 123';
        $user->Phonenumber[1]->phonenumber = '555 448';
        $user->save();
    }

    public function testSynchronizeRecord()
    {
        $user = Doctrine_Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $userArray = $user->toArray(true);
        $this->assertEqual($user->Phonenumber->count(), 2);
        $this->assertEqual($user->Phonenumber[0]->phonenumber, '555 123');

        // modify a Phonenumber
        $userArray['Phonenumber'][0]['phonenumber'] = '555 321';

        // delete a Phonenumber
        array_pop($userArray['Phonenumber']);

        $user->synchronizeFromArray($userArray);
        $this->assertEqual($user->Phonenumber->count(), 1);
        $this->assertEqual($user->Phonenumber[0]->phonenumber, '555 321');

        // change Email
        $userArray['Email']['address'] = 'johndow@mail.com';
        $user->synchronizeFromArray($userArray);
        $this->assertEqual($user->Email->address, 'johndow@mail.com');

        $user->save();
    }

    public function testSynchronizeAfterSaveRecord()
    {
        $user = Doctrine_Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $this->assertEqual($user->Phonenumber->count(), 1);
        $this->assertEqual($user->Phonenumber[0]->phonenumber, '555 321');
        $this->assertEqual($user->Email->address, 'johndow@mail.com');
    }

    public function testSynchronizeAddRecord()
    {
        $user = Doctrine_Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $userArray = $user->toArray(true);
        $userArray['Phonenumber'][] = array('phonenumber' => '333 238');

        $user->synchronizeFromArray($userArray);
        $this->assertEqual($user->Phonenumber->count(), 2);
        $this->assertEqual($user->Phonenumber[1]->phonenumber, '333 238');
        $user->save();
    }

    public function testSynchronizeAfterAddRecord()
    {
        $user = Doctrine_Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $this->assertEqual($user->Phonenumber->count(), 2);
        $this->assertEqual($user->Phonenumber[1]->phonenumber, '333 238');
    }

    public function testSynchronizeRemoveRecord()
    {
        $user = Doctrine_Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $userArray = $user->toArray(true);
        unset($userArray['Phonenumber']);
        unset($userArray['Email']);

        $user->synchronizeFromArray($userArray);
        $this->assertEqual($user->Phonenumber->count(), 0);
        $this->assertTrue(!isset($user->Email));
        $user->save();
    }

    public function testSynchronizeAfterRemoveRecord()
    {
        $user = Doctrine_Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $this->assertEqual($user->Phonenumber->count(), 0);
        $this->assertTrue(!isset($user->Email));
    }
}
