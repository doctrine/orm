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
 * Doctrine_Access_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Access_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    { }

    public function prepareTables() 
    {
        $this->tables = array('Entity', 'User'); 
        parent::prepareTables();
    }

    public function testUnset() 
    {

    }
    public function testIsset() 
    {
        $user = new User();

        $this->assertTrue(isset($user->name));  
        $this->assertFalse(isset($user->unknown));
        
        $this->assertTrue(isset($user['name']));
        $this->assertFalse(isset($user['unknown']));
        
        $coll = new Doctrine_Collection('User');
        
        $this->assertFalse(isset($coll[0]));
        // test repeated call
        $this->assertFalse(isset($coll[0]));
        $coll[0];
        
        $this->assertTrue(isset($coll[0]));
        // test repeated call
        $this->assertTrue(isset($coll[0]));
    }

    public function testOffsetMethods() 
    {
        $user = new User();
        $this->assertEqual($user['name'], null);

        $user['name'] = 'Jack';
        $this->assertEqual($user['name'], 'Jack');

        $user->save();

        $user = $this->connection->getRepository('User')->find($user->identifier());
        $this->assertEqual($user->name, 'Jack');

        $user['name'] = 'Jack';
        $this->assertEqual($user['name'], 'Jack');
        $user['name'] = 'zYne';
        $this->assertEqual($user['name'], 'zYne');
    }

    public function testOverload() 
    {
        $user = new User();
        $this->assertEqual($user->name,null);

        $user->name = 'Jack';

        $this->assertEqual($user->name, 'Jack');
        
        $user->save();

        $user = $this->connection->getRepository('User')->find($user->identifier());
        $this->assertEqual($user->name, 'Jack');

        $user->name = 'Jack';
        $this->assertEqual($user->name, 'Jack');
        $user->name = 'zYne';
        $this->assertEqual($user->name, 'zYne');
    }
    
    public function testSet() {
        $user = new User();
        $this->assertEqual($user->get('name'),null);

        $user->set('name', 'Jack');
        $this->assertEqual($user->get('name'), 'Jack');

        $user->save();

        $user = $this->connection->getRepository('User')->find($user->identifier());

        $this->assertEqual($user->get('name'), 'Jack');

        $user->set('name', 'Jack');
        $this->assertEqual($user->get('name'), 'Jack');
    }
}
