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
 * Doctrine_Query_Delete_TestCase
 * This test case is used for testing DQL UPDATE queries
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Update_TestCase extends Doctrine_UnitTestCase 
{

    public function testUpdateAllWithColumnAggregationInheritance() 
    {
        $q = new Doctrine_Query();

        $q->parseQuery("UPDATE User u SET u.name = 'someone'");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone' WHERE (type = 0)");

        $q = new Doctrine_Query();

        $q->update('User u')->set('u.name', "'someone'");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone' WHERE (type = 0)");
    }

    public function testUpdateWorksWithMultipleColumns() 
    {
        $q = new Doctrine_Query();

        $q->parseQuery("UPDATE User u SET u.name = 'someone', u.email_id = 5");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone', email_id = 5 WHERE (type = 0)");

        $q = new Doctrine_Query();

        $q->update('User u')->set('u.name', "'someone'")->set('u.email_id', 5);

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone', email_id = 5 WHERE (type = 0)");
    }
    
    public function testUpdateSupportsConditions() 
    {
        $q = new Doctrine_Query();

        $q->parseQuery("UPDATE User u SET u.name = 'someone' WHERE u.id = 5");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone' WHERE id = 5 AND (type = 0)");
    }
    public function testUpdateSupportsColumnReferencing()
    {
        $q = new Doctrine_Query();

        $q->update('User u')->set('u.id', 'u.id + 1');

        $this->assertEqual($q->getQuery(), "UPDATE entity SET id = id + 1 WHERE (type = 0)");
    }
}
