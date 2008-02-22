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
 * Doctrine_Ticket_642_TestCase
 *
 * @package     Doctrine
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Ticket_642_TestCase extends Doctrine_UnitTestCase 
{
    public function testInit()
    {
        $this->dbh = new Doctrine_Adapter_Mock('mysql');
        $this->conn = Doctrine_Manager::getInstance()->openConnection($this->dbh);
    }


    public function testTest()
    {
        $this->conn->export->exportClasses(array('stDummyObj'));
        $queries = $this->dbh->getAll();

        // Default was not being defined, even if notnull was set
        $this->assertEqual("CREATE TABLE st_dummy_obj (id BIGINT AUTO_INCREMENT, startdate DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL, PRIMARY KEY(id)) ENGINE = INNODB", $queries[1]);
	}
}


class stDummyObj extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setTableName('st_dummy_obj');
        $class->setColumn('startDate', 'timestamp', null, array(
            'notnull' => true, 
            'default' => '0000-00-00 00:00:00'
        ));
    }
}

?>