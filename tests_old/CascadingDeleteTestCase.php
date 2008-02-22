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
 * Doctrine_CascadingDelete_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_CascadingDelete_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData()
    { }
    public function prepareTables()
    { }
    public function testCascadingDeleteEmulation()
    {
        $r = new ForeignKeyTest;
        $r->name = 'Parent';
        $r->Children[0]->name = 'Child 1';
        $this->assertEqual($r->id, null);
        $this->assertEqual($r->Children[0]->id, null);
        $r->save();

        $this->assertEqual($r->id, 1);
        $this->assertEqual($r->Children[0]->id, 2);  


        $this->connection->clear();

        $r = $this->connection->query('FROM ForeignKeyTest');
        
        $this->assertEqual($r->count(), 2);
        
        // should delete the first child
        $r[0]->delete();

        $this->connection->clear();

        $r = $this->connection->query('FROM ForeignKeyTest');

        $this->assertEqual($r->count(), 0);
    }
    public function testCascadingDeleteEmulation2()
    {
        $r = new ForeignKeyTest;
        $r->name = 'Parent';
        $r->Children[0]->name = 'Child 1';
        $r->Children[0]->Children[0]->name = 'Child 1 Child 1';
        $r->Children[1]->name = 'Child 2';
        $r->save();

        $this->connection->clear();

        $r = $this->connection->query('FROM ForeignKeyTest');
        
        $this->assertEqual($r->count(), 4);
        
        // should delete the children recursively
        $r[0]->delete();

        $this->connection->clear();

        $r = $this->connection->query('FROM ForeignKeyTest');

        $this->assertEqual($r->count(), 0);
    }
}
