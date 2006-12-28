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
 * Doctrine_Query_Expression_TestCase
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Query_Expression_TestCase extends Doctrine_UnitTestCase {
    public function testUnknownExpressionInSelectClauseThrowsException() {
        $q = new Doctrine_Query();
        
        try {
            $q->parseQuery('SELECT SOMEUNKNOWNFUNC(u.name, " ", u.loginname) FROM User u');
            $this->fail();
        } catch(Doctrine_Query_Exception $e) {
            $this->pass();
        }
    }
    public function testUnknownColumnWithinFunctionInSelectClauseThrowsException() {
        $q = new Doctrine_Query();
        
        try {
            $q->parseQuery('SELECT CONCAT(u.name, u.unknown) FROM User u');
            $this->fail();
        } catch(Doctrine_Query_Exception $e) {
            $this->pass();
        }
    }
    public function testConcatIsSupportedInSelectClause() {
        $q = new Doctrine_Query();
        
        $q->parseQuery('SELECT CONCAT(u.name, u.loginname) FROM User u');
        
        $this->assertEqual($q->getQuery(), 'SELECT CONCAT(e.name, e.loginname) AS e__0 FROM entity e WHERE (e.type = 0)');
    }
    public function testConcatInSelectClauseSupportsLiteralStrings() {
        $q = new Doctrine_Query();
        
        $q->parseQuery("SELECT CONCAT(u.name, 'The Man') FROM User u");
        
        $this->assertEqual($q->getQuery(), "SELECT CONCAT(e.name, 'The Man') AS e__0 FROM entity e WHERE (e.type = 0)");
    }
    public function testConcatInSelectClauseSupportsMoreThanTwoArgs() {
        $q = new Doctrine_Query();
        
        $q->parseQuery("SELECT CONCAT(u.name, 'The Man', u.loginname) FROM User u");
        
        $this->assertEqual($q->getQuery(), "SELECT CONCAT(e.name, 'The Man', e.loginname) AS e__0 FROM entity e WHERE (e.type = 0)");
    }
}
