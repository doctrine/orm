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
 * Doctrine_Relation_Parser_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Relation_Parser_TestCase extends Doctrine_UnitTestCase 
{
    public function testPendingRelations()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));
        
        $p = array('type' => Doctrine_Relation::ONE, 
                   'local' => 'email_id',
                   'definer' => 'User');

        $r->bind('Email', $p);
                                
        $this->assertEqual($r->getPendingRelation('Email'), array('type' => Doctrine_Relation::ONE, 
                                                                  'local' => 'email_id',
                                                                  'class' => 'Email',
                                                                  'alias' => 'Email',
                                                                  'definer' => 'User'
                                                                  ));
    }
    public function testBindThrowsExceptionIfDefinerNotSet() 
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));
        
        $p = array('type' => Doctrine_Relation::ONE, 
                   'local' => 'email_id');
        try {
            $r->bind('Email', $p);
            $this->fail('should throw exception');
        } catch(Doctrine_Relation_Exception $e) {
            $this->pass();
        }
    }
    public function testBindThrowsExceptionIfTypeNotSet()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));

        $p = array('local' => 'email_id',
                   'definer' => 'User');
        try {
            $r->bind('Email', $p);
            $this->fail('should throw exception');
        } catch(Doctrine_Relation_Exception $e) {
            $this->pass();
        }
    }
    public function testRelationParserSupportsLocalColumnGuessing()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));

        $d = $r->completeDefinition(array('class'   => 'Phonenumber',
                                          'type'    => Doctrine_Relation::MANY,
                                          'foreign' => 'entity_id',
                                          'definer' => 'Entity'));
        
        $this->assertEqual($d['local'], 'id');
    }
    public function testRelationParserSupportsLocalColumnGuessing2()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));

        $d = $r->completeDefinition(array('class'   => 'Email',
                                          'type'    => Doctrine_Relation::ONE,
                                          'foreign' => 'id',
                                          'definer' => 'User'));

        $this->assertEqual($d['local'], 'email_id');
    }
    public function testRelationParserSupportsForeignColumnGuessing()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));

        $d = $r->completeDefinition(array('class' => 'Phonenumber',
                                          'type'  => Doctrine_Relation::MANY,
                                          'local' => 'id',
                                          'definer' => 'Entity'));

        $this->assertEqual($d['foreign'], 'entity_id');
    }
    public function testRelationParserSupportsForeignColumnGuessing2()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));

        $d = $r->completeDefinition(array('class' => 'Email',
                                          'type'  => Doctrine_Relation::ONE,
                                          'local' => 'email_id',
                                          'definer' => 'User'));

        $this->assertEqual($d['foreign'], 'id');
    }
    public function testRelationParserSupportsGuessingOfBothColumns()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));

        $d = $r->completeDefinition(array('class' => 'Email',
                                          'type'  => Doctrine_Relation::MANY,
                                          'definer' => 'User'));

        $this->assertEqual($d['foreign'], 'id');
        $this->assertEqual($d['local'], 'email_id');
    }
    public function testRelationParserSupportsForeignColumnGuessingForAssociations()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));

        $d = $r->completeAssocDefinition(array('class'    => 'Group',
                                               'type'     => Doctrine_Relation::MANY,
                                               'local'    => 'user_id',
                                               'refClass' => 'GroupUser',
                                               'definer'  => 'User'));

        $this->assertEqual($d['foreign'], 'group_id');
    }
    public function testRelationParserSupportsLocalColumnGuessingForAssociations()
    {
        $r = new Doctrine_Relation_Parser($this->conn->getTable('User'));

        $d = $r->completeAssocDefinition(array('class'    => 'Group',
                                               'type'     => Doctrine_Relation::MANY,
                                               'foreign'  => 'group_id',
                                               'refClass' => 'GroupUser',
                                               'definer'  => 'User'));

        $this->assertEqual($d['local'], 'user_id');
    }
}
