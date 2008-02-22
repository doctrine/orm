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
 * Doctrine_Query_Orderby_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Orderby_TestCase extends Doctrine_UnitTestCase 
{
    public function testOrderByRandomIsSupported()
    {
        $q = new Doctrine_Query();
        
        $q->select('u.name, RANDOM() rand')
          ->from('User u')
          ->orderby('rand DESC');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, ((RANDOM() + 2147483648) / 4294967296) AS e__0 FROM entity e WHERE (e.type = 0) ORDER BY e__0 DESC');
    }
    public function testOrderByAggregateValueIsSupported()
    {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(p.phonenumber) count')
          ->from('User u')
          ->leftJoin('u.Phonenumber p')
          ->orderby('count DESC');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, COUNT(p.phonenumber) AS p__0 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) ORDER BY p__0 DESC');
    }
    /* ticket #681 */
    public function testOrderByWithCoalesce()
    {
        $q = new Doctrine_Query();
        
        $q->select('u.name')
          ->from('User u')
          ->orderby('COALESCE(u.id, u.name) DESC');
        // nonesese results expected, but query is syntatically ok.
        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0) ORDER BY COALESCE(e__id, e__name) DESC');
    }
}
