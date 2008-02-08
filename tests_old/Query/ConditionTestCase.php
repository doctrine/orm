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
 * Doctrine_Query_Condition_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Condition_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() { }
    public function prepareTables() { }
    
    /** @todo belongs in TokenizerTestCase? */
    public function testBracktExplode() 
    {
        $tokenizer = new Doctrine_Query_Tokenizer();
        $str   = "item OR item || item";
        $parts = $tokenizer->bracketExplode($str, array(' \|\| ', ' OR '), "(", ")");

        $this->assertEqual($parts, array('item','item','item'));

    }
    public function testConditionParser() 
    {
        $query = new Doctrine_Query($this->connection);

        $query->select('User.id')->from("User")->where("User.name LIKE 'z%' || User.name LIKE 's%'");

        $sql = "SELECT e.id AS e__id FROM entity e WHERE (e.name LIKE 'z%' OR e.name LIKE 's%') AND (e.type = 0)";
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') || (User.name LIKE 's%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((User.name LIKE 'z%') || (User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || (User.name LIKE 's%')))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') || User.name LIKE 's%' && User.name LIKE 'a%'");

        $sql = "SELECT e.id AS e__id FROM entity e WHERE ((e.name LIKE 'z%' OR e.name LIKE 's%') AND e.name LIKE 'a%') AND (e.type = 0)";

        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%'");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%'))");
        $this->assertEqual($query->getQuery(), $sql);

    }

    public function testConditionParser2() 
    {
        $query = new Doctrine_Query($this->connection);

        $query->select('User.id')->from("User")->where("User.name LIKE 'z%' || User.name LIKE 's%'");

        $sql = "SELECT e.id AS e__id FROM entity e WHERE (e.name LIKE 'z%' OR e.name LIKE 's%') AND (e.type = 0)";
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') OR (User.name LIKE 's%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((User.name LIKE 'z%') OR (User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR (User.name LIKE 's%')))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') OR User.name LIKE 's%' AND User.name LIKE 'a%'");

        $sql = "SELECT e.id AS e__id FROM entity e WHERE ((e.name LIKE 'z%' OR e.name LIKE 's%') AND e.name LIKE 'a%') AND (e.type = 0)";

        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%'");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%'))");
        $this->assertEqual($query->getQuery(), $sql);
    }
}
