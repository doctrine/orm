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
 * Doctrine_Export_CheckConstraint_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export_CheckConstraint_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData()
    { }
    public function prepareTables()
    { }
    
    public function testCheckConstraints()
    {
        $e = $this->conn->export;

        $sql = $e->exportClassesSql(array('CheckConstraintTest'));
   
        $this->assertEqual($sql[0], 'CREATE TABLE check_constraint_test (id INTEGER PRIMARY KEY AUTOINCREMENT, price DECIMAL(2,2), discounted_price DECIMAL(2,2)), CHECK (price > 100), CHECK (price < 5000), CHECK (price > discounted_price))');
    
        try {
            $dbh = new PDO('sqlite::memory:');
            $dbh->exec($sql[0]);
            $this->pass();
        } catch (PDOException $e) {
            $this->fail();
        }
    }
}
class CheckConstraintTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('price', 'decimal', 2, array('max' => 5000, 'min' => 100));
        $this->hasColumn('discounted_price', 'decimal', 2);
        $this->check('price > discounted_price');
    }
}
