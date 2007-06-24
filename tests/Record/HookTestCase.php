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
 * Doctrine_Record_Hook_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_Hook_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData()
    { }
    public function prepareTables() 
    { 
        $this->tables = array('RecordHookTest');
        
        parent::prepareTables();
    }
    
    public function testInsertHooksGetInvoked()
    {
        $r = new RecordHookTest();
        
        $r->name = 'record';
        $r->save();

        $this->assertEqual($r->pop(), 'postSave');
        $this->assertEqual($r->pop(), 'postInsert');
        $this->assertEqual($r->pop(), 'preInsert');
        $this->assertEqual($r->pop(), 'preSave');
    }

    public function testUpdateHooksGetInvoked()
    {
        $records = Doctrine_Query::create()->from('RecordHookTest t')->where("t.name = 'record'")->execute();
        $r = $records[0];

        $r->name = 'record 2';
        $r->save();

        $this->assertEqual($r->pop(), 'postSave');
        $this->assertEqual($r->pop(), 'postUpdate');
        $this->assertEqual($r->pop(), 'preUpdate');
        $this->assertEqual($r->pop(), 'preSave');
    }
    
    public function testDeleteHooksGetInvoked()
    {
        $records = Doctrine_Query::create()->from('RecordHookTest t')->where("t.name = 'record 2'")->execute();
        $r = $records[0];

        $r->delete();

        $this->assertEqual($r->pop(), 'postDelete');
        $this->assertEqual($r->pop(), 'preDelete');
    }
}
class RecordHookTest extends Doctrine_Record
{
    protected $_messages = array();

    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', null, array('primary' => true));
    }
    public function preSave()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postSave()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preInsert()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postInsert()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preUpdate()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postUpdate()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preDelete()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postDelete()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function pop()
    {
        return array_pop($this->_messages);
    }
}

