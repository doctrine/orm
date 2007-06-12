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
 * Doctrine_Query_IdentifierQuoting_TestCase
 *
 * This test case is used for testing DQL API quotes all identifiers properly
 * if idenfitier quoting is turned on
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_IdentifierQuoting_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }
    public function prepareData() { }
    public function testQuerySupportsIdentifierQuoting() {
        $this->conn->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, true);

        $q = new Doctrine_Query();
        
        $q->parseQuery('SELECT MAX(u.id), MIN(u.name) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(e.id) AS e__0, MIN(e.name) AS e__1 FROM "entity" e WHERE (e.type = 0)');

    }
    public function testQuerySupportsIdentifierQuotingWithJoins() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM "entity" e LEFT JOIN "phonenumber" p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    
    public function testLimitSubqueryAlgorithmSupportsIdentifierQuoting() {
        $q = new Doctrine_Query();
        
        $q->parseQuery('SELECT u.name FROM User u INNER JOIN u.Phonenumber p')->limit(5); 
        
        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM "entity" e INNER JOIN "phonenumber" p ON e.id = p.entity_id WHERE e.id IN (SELECT DISTINCT e2.id FROM "entity" e2 INNER JOIN "phonenumber" p2 ON e2.id = p2.entity_id WHERE (e2.type = 0) LIMIT 5) AND (e.type = 0)');
        
        $this->conn->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, false);
    }
}
