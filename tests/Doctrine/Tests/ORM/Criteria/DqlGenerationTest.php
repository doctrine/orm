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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Test case for testing the saving and referencing of query identifiers.
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class DqlGenerationTest extends \Doctrine\Tests\OrmTestCase
{
    protected function setUp() {
        $this->markTestSkipped('Not yet implemented.');
    }

    protected function createQuery()
    {
        return $this->_em->createQuery();
    }

    public function testSelect()
    {
        $query = $this->createQuery();

        // select and from
        $query->setDql('FROM User u');
        $this->assertEquals('FROM User u', $query->getDql()); // Internally we use SELECT * FROM User u to process the DQL
        $query->free();

        $query->select()->from('User u');
        $this->assertEquals('SELECT * FROM User u', $query->getDql());
        $query->free();

        $query->select('u.*')->from('User u');
        $this->assertEquals('SELECT u.* FROM User u', $query->getDql());
        $query->free();

        $query->select('u.id')->from('User u');
        $this->assertEquals('SELECT u.id FROM User u', $query->getDql());
        $query->free();

        $query->select('u.id, u.name')->from('User u');
        $this->assertEquals('SELECT u.id, u.name FROM User u', $query->getDql());
        $query->free();

        $query->select('u.name AS myCustomName')->from('User u');
        $this->assertEquals('SELECT u.name AS myCustomName FROM User u', $query->getDql());
        $query->free();

        $query->select('u.id')->select('u.name')->from('User u');
        $this->assertEquals('SELECT u.id, u.name FROM User u', $query->getDql());
        $query->free();
    }


    public function testSelectDistinct()
    {
        $query = $this->createQuery();

        $query->select()->distinct()->from('User u');
        $this->assertEquals('SELECT DISTINCT * FROM User u', $query->getDql());
        $query->free();

        $query->select('u.name')->distinct(false)->from('User u');
        $this->assertEquals('SELECT u.name FROM User u', $query->getDql());
        $query->free();

        $query->select()->distinct(false)->from('User u');
        $this->assertEquals('SELECT * FROM User u', $query->getDql());
        $query->free();

        $query->select('u.name')->distinct()->from('User u');
        $this->assertEquals('SELECT DISTINCT u.name FROM User u', $query->getDql());
        $query->free();

        $query->select('u.name, u.email')->distinct()->from('User u');
        $this->assertEquals('SELECT DISTINCT u.name, u.email FROM User u', $query->getDql());
        $query->free();

        $query->select('u.name')->select('u.email')->distinct()->from('User u');
        $this->assertEquals('SELECT DISTINCT u.name, u.email FROM User u', $query->getDql());
        $query->free();

        $query->select('DISTINCT u.name')->from('User u');
        $this->assertEquals('SELECT DISTINCT u.name FROM User u', $query->getDql());
        $query->free();

        $query->select('DISTINCT u.name, u.email')->from('User u');
        $this->assertEquals('SELECT DISTINCT u.name, u.email FROM User u', $query->getDql());
        $query->free();

        $query->select('DISTINCT u.name')->select('u.email')->from('User u');
        $this->assertEquals('SELECT DISTINCT u.name, u.email FROM User u', $query->getDql());
        $query->free();
    }


    public function testSelectJoin()
    {
        $query = $this->createQuery();

        $query->select('u.*')->from('User u')->join('u.Group g')->where('g.id = ?', 1);
        $this->assertEquals('SELECT u.* FROM User u INNER JOIN u.Group g WHERE g.id = ?', $query->getDql());
        $this->assertEquals(array(1), $query->getParams());
        $query->free();

        $query->select('u.*')->from('User u')->innerJoin('u.Group g')->where('g.id = ?', 1);
        $this->assertEquals('SELECT u.* FROM User u INNER JOIN u.Group g WHERE g.id = ?', $query->getDql());
        $this->assertEquals(array(1), $query->getParams());
        $query->free();

        $query->select('u.*')->from('User u')->leftJoin('u.Group g')->where('g.id IS NULL');
        $this->assertEquals('SELECT u.* FROM User u LEFT JOIN u.Group g WHERE g.id IS NULL', $query->getDql());
        $query->free();

        $query->select('u.*')->from('User u')->leftJoin('u.UserGroup ug')->leftJoin('ug.Group g')->where('g.name = ?', 'admin');
        $this->assertEquals('SELECT u.* FROM User u LEFT JOIN u.UserGroup ug LEFT JOIN ug.Group g WHERE g.name = ?', $query->getDql());
        $query->free();
    }


    public function testSelectWhere()
    {
        $query = $this->createQuery();

        $query->select('u.name')->from('User u')->where('u.id = ?', 1);
        $this->assertEquals('SELECT u.name FROM User u WHERE u.id = ?', $query->getDql());
        $this->assertEquals(array(1), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->where('u.id = ? AND u.type != ?', array(1, 'admin'));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.id = ? AND u.type != ?', $query->getDql());
        $this->assertEquals(array(1, 'admin'), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->where('u.id = ?', 1)->andWhere('u.type != ?', 'admin');
        $this->assertEquals('SELECT u.name FROM User u WHERE u.id = ? AND u.type != ?', $query->getDql());
        $this->assertEquals(array(1, 'admin'), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->where('( u.id = ?', 1)->andWhere('u.type != ? )', 'admin');
        $this->assertEquals('SELECT u.name FROM User u WHERE ( u.id = ? AND u.type != ? )', $query->getDql());
        $this->assertEquals(array(1, 'admin'), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->where('u.id = ? OR u.type != ?', array(1, 'admin'));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.id = ? OR u.type != ?', $query->getDql());
        $this->assertEquals(array(1, 'admin'), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->where('u.id = ?', 1)->orWhere('u.type != ?', 'admin');
        $this->assertEquals('SELECT u.name FROM User u WHERE u.id = ? OR u.type != ?', $query->getDql());
        $this->assertEquals(array(1, 'admin'), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->andwhere('u.id = ?', 1)->andWhere('u.type != ?', 'admin')->orWhere('u.email = ?', 'admin@localhost');
        $this->assertEquals('SELECT u.name FROM User u WHERE u.id = ? AND u.type != ? OR u.email = ?', $query->getDql());
        $this->assertEquals(array(1, 'admin', 'admin@localhost'), $query->getParams());
        $query->free();
    }


    public function testSelectWhereIn()
    {
        $query = $this->createQuery();

        $query->select('u.name')->from('User u')->whereIn('u.id', array(1, 2, 3, 4, 5));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.id IN (?, ?, ?, ?, ?)', $query->getDql());
        $this->assertEquals(array(1, 2, 3, 4, 5), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->whereNotIn('u.id', array(1, 2, 3));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.id NOT IN (?, ?, ?)', $query->getDql());
        $this->assertEquals(array(1, 2, 3), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->where('u.type = ?', 'admin')->andWhereIn('u.id', array(1, 2));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.type = ? AND u.id IN (?, ?)', $query->getDql());
        $this->assertEquals(array('admin', 1, 2), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->where('u.type = ?', 'admin')->andWhereNotIn('u.id', array(1, 2));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.type = ? AND u.id NOT IN (?, ?)', $query->getDql());
        $this->assertEquals(array('admin', 1, 2), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->whereIn('u.type', array('admin', 'moderator'))->andWhereNotIn('u.id', array(1, 2, 3, 4));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.type IN (?, ?) AND u.id NOT IN (?, ?, ?, ?)', $query->getDql());
        $this->assertEquals(array('admin', 'moderator', 1, 2, 3, 4), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->whereIn('u.type', array('admin', 'moderator'))->orWhereIn('u.id', array(1, 2, 3, 4));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.type IN (?, ?) OR u.id IN (?, ?, ?, ?)', $query->getDql());
        $this->assertEquals(array('admin', 'moderator', 1, 2, 3, 4), $query->getParams());
        $query->free();

        $query->select('u.name')->from('User u')->whereIn('u.type', array('admin', 'moderator'))->andWhereNotIn('u.id', array(1, 2))->orWhereNotIn('u.type', array('admin', 'moderator'))->andWhereNotIn('u.email', array('user@localhost', 'guest@localhost'));
        $this->assertEquals('SELECT u.name FROM User u WHERE u.type IN (?, ?) AND u.id NOT IN (?, ?) OR u.type NOT IN (?, ?) AND u.email NOT IN (?, ?)', $query->getDql());
        $this->assertEquals(array('admin', 'moderator', 1, 2, 'admin', 'moderator', 'user@localhost', 'guest@localhost'), $query->getParams());
        $query->free();
    }


    public function testDelete()
    {
        $query = $this->createQuery();

        $query->setDql('DELETE CmsUser u');
        $this->assertEquals('DELETE CmsUser u', $query->getDql());
        $query->free();

        $query->delete()->from('CmsUser u');
        $this->assertEquals('DELETE FROM CmsUser u', $query->getDql());
        $query->free();

        $query->delete()->from('CmsUser u')->where('u.id = ?', 1);
        $this->assertEquals('DELETE FROM CmsUser u WHERE u.id = ?', $query->getDql());
        $query->free();

        $query->delete()->from('CmsUser u')->where('u.username = ?', 'gblanco')->orWhere('u.name = ?', 'Guilherme');
        $this->assertEquals('DELETE FROM CmsUser u WHERE u.username = ? OR u.name = ?', $query->getDql());
        $query->free();
    }

}
