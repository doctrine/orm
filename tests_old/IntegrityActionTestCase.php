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
 * Doctrine_IntegrityAction_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_IntegrityAction_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData()
    { }
    public function prepareTables()
    {
        $this->tables = array('CascadeDeleteTest', 'CascadeDeleteRelatedTest', 'CascadeDeleteRelatedTest2');
        
        parent::prepareTables();
    }
    public function testIntegrityActionsAreAddedIntoGlobalActionsArray()
    {
        $c = new CascadeDeleteTest;
        $c2 = new CascadeDeleteRelatedTest;

        $expected = array('CascadeDeleteRelatedTest' => 'CASCADE');
        $this->assertEqual($this->manager->getDeleteActions('CascadeDeleteTest'), $expected);
        
        $expected = array('CascadeDeleteRelatedTest' => 'SET NULL');
        $this->assertEqual($this->manager->getUpdateActions('CascadeDeleteTest'), $expected);
    }
    public function testOnDeleteCascadeEmulation()
    {
        $c = new CascadeDeleteTest;
        $c->name = 'c 1';
        $c->Related[]->name = 'r 1';
        $c->Related[]->name = 'r 2';
        $c->Related[0]->Related[]->name = 'r r 1';
        $c->Related[1]->Related[]->name = 'r r 2';
        
        $c->save();
        
        $this->connection->clear();
        
        $c = $this->conn->queryOne('FROM CascadeDeleteTest c WHERE c.id = 1');
        
        $c->delete();
    }
}
