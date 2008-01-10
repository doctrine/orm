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
 * Doctrine_Ticket_480_TestCase
 *
 * @package     Doctrine
 * @author      Miloslav Kmet <adrive-nospam@hip-hop.sk>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

class stComment extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('st_comment');
        $this->hasColumn('title', 'string', 100, array());
        $this->hasColumn('body', 'string', 1000, array());
    }
}

class Doctrine_Ticket_480_TestCase extends Doctrine_UnitTestCase
{
    public function testInit()
    {
                $this->dbh = new Doctrine_Adapter_Mock('oracle');
                $this->conn = Doctrine_Manager::getInstance()->openConnection($this->dbh);
    }

    public function testTicket()
    {
        $this->conn->export->exportClasses(array('stComment'));
        $queries = $this->dbh->getAll();

        // (2nd|1st except transaction init.) executed query must be CREATE TABLE or CREATE SEQUENCE, not CREATE TRIGGER
        // Trigger can be created after both CREATE TABLE and CREATE SEQUENCE
        $this->assertFalse(preg_match('~^CREATE TRIGGER.*~', $queries[1]));
        $this->assertFalse(preg_match('~^CREATE TRIGGER.*~', $queries[2]));
    }
}
