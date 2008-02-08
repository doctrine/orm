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
 * Doctrine_ForeignKey_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_ForeignKey_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData()
    { }
    public function prepareTables()
    { }
    

    public function testExportingForeignKeysSupportsAssociationTables()
    {
        $this->dbh = new Doctrine_Adapter_Mock('mysql');
        $this->conn = $this->manager->openConnection($this->dbh);
        
        $sql = $this->conn->export->exportClassesSql(array('ClientModel', 'ClientToAddressModel', 'AddressModel'));
        
        $this->assertEqual($sql, array (
                                        0 => 'CREATE TABLE clients_to_addresses (client_id BIGINT, address_id BIGINT, INDEX client_id_idx (client_id), INDEX address_id_idx (address_id), PRIMARY KEY(client_id, address_id)) ENGINE = INNODB',
                                        1 => 'CREATE TABLE clients (id INT UNSIGNED NOT NULL AUTO_INCREMENT, short_name VARCHAR(32) NOT NULL UNIQUE, PRIMARY KEY(id)) ENGINE = INNODB',
                                        2 => 'CREATE TABLE addresses (id BIGINT AUTO_INCREMENT, address1 VARCHAR(255) NOT NULL, address2 VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, state VARCHAR(10) NOT NULL, zip VARCHAR(15) NOT NULL, PRIMARY KEY(id)) ENGINE = INNODB',
                                        3 => 'ALTER TABLE clients_to_addresses ADD CONSTRAINT FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE',
                                        4 => 'ALTER TABLE clients_to_addresses ADD CONSTRAINT FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE CASCADE',
                                      ));
    }
}
