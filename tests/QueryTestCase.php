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
 * Doctrine_Query_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_TestCase extends Doctrine_UnitTestCase
{

    public function testGetQueryHookResetsTheManuallyAddedDqlParts()
    {
        $q = new MyQuery();

        $q->from('User u');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE e.id = 4 AND (e.type = 0)');

        // test consequent calls
        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE e.id = 4 AND (e.type = 0)');
    }


    public function testParseClauseSupportsArithmeticOperators()
    {
        $q = new Doctrine_Query();

        $str = $q->parseClause('2 + 3');

        $this->assertEqual($str, '2 + 3');

        $str = $q->parseClause('2 + 3 - 5 * 6');

        $this->assertEqual($str, '2 + 3 - 5 * 6');
    }
    
    public function testParseClauseSupportsArithmeticOperatorsWithFunctions()
    {
        $q = new Doctrine_Query();

        $str = $q->parseClause('ACOS(2) + 3');

        $this->assertEqual($str, 'ACOS(2) + 3');
    }

    public function testParseClauseSupportsArithmeticOperatorsWithParenthesis()
    {
        $q = new Doctrine_Query();

        $str = $q->parseClause('(3 + 3)*3');

        $this->assertEqual($str, '(3 + 3)*3');

        $str = $q->parseClause('((3 + 3)*3 - 123) * 12 * (13 + 31)');

        $this->assertEqual($str, '((3 + 3)*3 - 123) * 12 * (13 + 31)');
    }

    public function testParseClauseSupportsArithmeticOperatorsWithParenthesisAndFunctions()
    {
        $q = new Doctrine_Query();

        $str = $q->parseClause('(3 + 3)*ACOS(3)');

        $this->assertEqual($str, '(3 + 3)*ACOS(3)');

        $str = $q->parseClause('((3 + 3)*3 - 123) * ACOS(12) * (13 + 31)');

        $this->assertEqual($str, '((3 + 3)*3 - 123) * ACOS(12) * (13 + 31)');
    }

    public function testParseClauseSupportsComponentReferences()
    {
        $q = new Doctrine_Query();
        $q->from('User u')->leftJoin('u.Phonenumber p');
        $q->getQuery();
        //Doctrine::dump($q->getCachedForm(array('foo' => 'bar')));
        $this->assertEqual($q->parseClause("CONCAT('u.name', u.name)"), "'u.name' || e.name");
    }
    
    public function testUsingDuplicateClassAliasThrowsException()
    {
        $q = new Doctrine_Query();
        $q->from('User u')->leftJoin('u.Phonenumber u');
        try {
            $q->getSqlQuery();
            $this->fail();
        } catch (Doctrine_Query_Exception $e) {
            $this->pass();
        }
        
        $q = new Doctrine_Query();
        $q->parseDqlQuery('FROM User u, u.Phonenumber u');
        try {
            $q->getSqlQuery();
            $this->fail();
        } catch (Doctrine_Query_Exception $e) {
            $this->pass();
        }
    }
}
class MyQuery extends Doctrine_Query
{
    public function preQuery()
    {
        $this->where('u.id = 4');
    }
}
