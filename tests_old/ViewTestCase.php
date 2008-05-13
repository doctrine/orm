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
 * Doctrine_View_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_View_TestCase extends Doctrine_UnitTestCase 
{

    public function testCreateView()
    {
        $query = new Doctrine_Query($this->connection);
        $query->from('User');

        $view = new Doctrine_View($query, 'MyView');

        $this->assertEqual($view->getName(), 'MyView');

        $this->assertTrue($view->getQuery() === $query);
        $this->assertTrue($view === $query->getView());
        $this->assertTrue($view->getConnection() instanceof Doctrine_Connection);

        $success = true;

        try {
            $view->create();
        } catch(Exception $e) {
            $success = false;
        }
        $this->assertTrue($success);

        $users = $view->execute();
        $count = $this->conn->count();
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual($users->count(), 8);
        $this->assertEqual($users[0]->name, 'zYne');
        $this->assertEqual($users[0]->state(), Doctrine_Entity::STATE_CLEAN);
        $this->assertEqual($count, $this->conn->count());

        $success = true;
        try {
            $view->drop();
        } catch(Exception $e) {
            $success = false;
        }
        $this->assertTrue($success);
    }

    public function testConstructor() 
    {
    }
}
