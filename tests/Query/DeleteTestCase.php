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
 * Doctrine_Query_Delete_TestCase
 * This test case is used for testing DQL DELETE queries
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Delete_TestCase extends Doctrine_UnitTestCase 
{
    public function testDeleteAllWithColumnAggregationInheritance() 
    {
        $q = new Doctrine_Query2();

        $q->parseQuery('DELETE FROM User');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity WHERE (type = 0)');

        $q = new Doctrine_Query2();

        $q->delete()->from('User');
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity WHERE (type = 0)');
    }
    public function testDeleteAll() 
    {
        $q = new Doctrine_Query2();

        $q->parseQuery('DELETE FROM Entity');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity');
        
        $q = new Doctrine_Query2();

        $q->delete()->from('Entity');
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity');
    }
    public function testDeleteWithCondition() 
    {
        $q = new Doctrine_Query2();

        $q->parseQuery('DELETE FROM Entity WHERE id = 3');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity WHERE id = 3');
        
        $q = new Doctrine_Query2();

        $q->delete()->from('Entity')->where('id = 3');
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity WHERE id = 3');
    }
    public function testDeleteWithLimit() 
    {
        $q = new Doctrine_Query2();

        $q->parseQuery('DELETE FROM Entity LIMIT 20');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity LIMIT 20');
        
        $q = new Doctrine_Query2();

        $q->delete()->from('Entity')->limit(20);
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity LIMIT 20');
    }
    public function testDeleteWithLimitAndOffset() 
    {
        $q = new Doctrine_Query2();

        $q->parseQuery('DELETE FROM Entity LIMIT 10 OFFSET 20');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity LIMIT 10 OFFSET 20');

        $q = new Doctrine_Query2();

        $q->delete()->from('Entity')->limit(10)->offset(20);
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity LIMIT 10 OFFSET 20');
    }
}
?>
