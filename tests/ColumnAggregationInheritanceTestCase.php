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
 * Doctrine_ColumnAggregationInheritance_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Bjarte Stien Karlsen <bjartka@pvv.ntnu.no> 
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_ColumnAggregationInheritance_TestCase extends Doctrine_UnitTestCase 
{
    protected $otherEntity = null;

    public function prepareData()
    {
        parent::prepareData();
        //we create a test entity that is not a user and not a group
        $entity = new Entity();
        $entity->name='Other Entity';
        $entity->type = 2; 
        $entity->save();
        $this->otherEntity = $entity;
    }

    public function testQueriedClassReturnedIfNoSubclassMatch()
    {
        $q = new Doctrine_Query();
        $entityOther = $q->from('Entity')->where('id = ?')->execute(array($this->otherEntity->id))->getFirst();
        $this->assertTrue($entityOther instanceOf Entity);
    }

    public function testSubclassReturnedIfInheritanceMatches()
    {
        $q = new Doctrine_Query();
        $group = $q->from('Entity')->where('id=?')->execute(array(1))->getFirst();
        $this->assertTrue($group instanceOf Group);

        $q = new Doctrine_Query();
        $user = $q->from('Entity')->where('id=?')->execute(array(5))->getFirst();
        $this->assertTrue($user instanceOf User);
    }
    public function testStringColumnInheritance()
    {
        $q = new Doctrine_Query();
        $q->select('g.name')->from('Group g');
        $this->assertEqual($q->getSql(), "SELECT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 1)");
    }

    public function testSubclassFieldSetWhenCreatingNewSubclassedRecord()
    {
        $child = new User();
        $child->name = 'Pedro';
        $this->assertTrue(isset($child->type));

        $child->save();
        $this->assertEqual($child->type, '0');
    }
}
