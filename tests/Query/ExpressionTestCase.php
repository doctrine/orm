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
class Doctrine_Query_Expression_TestCase extends Doctrine_UnitTestCase 
{

    public function testUnknownExpressionInSelectClauseThrowsException() 
    {
        $q = new Doctrine_Query();
        
        try {
            $q->parseQuery('SELECT SOMEUNKNOWNFUNC(u.name, " ", u.loginname) FROM User u');
            
            $q->getQuery();
            $this->fail();
        } catch(Doctrine_Query_Exception $e) {
            $this->pass();
        }
    }

    public function testUnknownColumnWithinFunctionInSelectClauseThrowsException() 
    {
        $q = new Doctrine_Query();
        
        try {
            $q->parseQuery('SELECT CONCAT(u.name, u.unknown) FROM User u');
            
            $q->execute();
            $this->fail();
        } catch(Doctrine_Query_Exception $e) {
            $this->pass();
        }
    }

    public function testConcatIsSupportedInSelectClause() 
    {
        $q = new Doctrine_Query();
        
        $q->parseQuery('SELECT CONCAT(u.name, u.loginname) FROM User u');
        
        $this->assertEqual($q->getQuery(), 'SELECT CONCAT(e.name, e.loginname) AS e__0 FROM entity e WHERE (e.type = 0)');
    }

    public function testConcatInSelectClauseSupportsLiteralStrings() 
    {
        $q = new Doctrine_Query();
        
        $q->parseQuery("SELECT CONCAT(u.name, 'The Man') FROM User u");
        
        $this->assertEqual($q->getQuery(), "SELECT CONCAT(e.name, 'The Man') AS e__0 FROM entity e WHERE (e.type = 0)");
    }

    public function testConcatInSelectClauseSupportsMoreThanTwoArgs() 
    {
        $q = new Doctrine_Query();
        
        $q->parseQuery("SELECT CONCAT(u.name, 'The Man', u.loginname) FROM User u");
        
        $this->assertEqual($q->getQuery(), "SELECT CONCAT(e.name, 'The Man', e.loginname) AS e__0 FROM entity e WHERE (e.type = 0)");
    }

    public function testNonPortableFunctionsAreSupported()
    {
         $query = new Doctrine_Query();
         // we are using stored procedure here, so adjust portability settings
         $this->conn->setAttribute(Doctrine::ATTR_PORTABILITY, Doctrine::PORTABILITY_ALL ^ Doctrine::PORTABILITY_EXPR);

         $lat = '13.23';
         $lon = '33.23';
         $radius = '33';

         $query->select("l.*, i18n.*, GeoDistKM(l.lat, l.lon, $lat, $lon) distance")
              ->from('Location l, l.LocationI18n i18n')          
              ->where('l.id <> ? AND i18n.culture = ?', array(1, 'en'))
              ->having("distance < $radius")
              ->orderby('distance ASC')
              ->groupby('l.id')
              ->limit(5);

         $this->assertEqual($query->getSql(), "SELECT l.id AS l__id, l.lat AS l__lat, l.lon AS l__lon, l2.name AS l2__name, l2.id AS l2__id, l2.culture AS l2__culture, GeoDistKM(l.lat, l.lon, 13.23, 33.23) AS l__0 FROM location l LEFT JOIN location_i18n l2 ON l.id = l2.id WHERE l.id IN (SELECT DISTINCT l3.id FROM location l3 LEFT JOIN location_i18n l4 ON l3.id = l4.id WHERE (l3.id <> ? AND l4.culture = ?) GROUP BY l3.id HAVING l__0 < 33 ORDER BY l__0 ASC LIMIT 5) AND (l.id <> ? AND l2.culture = ?) GROUP BY l.id HAVING l__0 < 33 ORDER BY l__0 ASC");

         $this->conn->setAttribute(Doctrine::ATTR_PORTABILITY, Doctrine::PORTABILITY_ALL);
    }
}
class Location extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('lat', 'double', 10, array ());
        $this->hasColumn('lon', 'double', 10, array ());
    }

    public function setUp()
    {
        $this->hasMany('LocationI18n as LocationI18n', array('local' => 'id', 'foreign' => 'id'));
    }
}
 
class LocationI18n extends Doctrine_Record
{ 
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 50, array());
        $this->hasColumn('id', 'integer', 10, array('primary' => true));
        $this->hasColumn('culture', 'string', 2);
    }
    
    public function setUp()
    {
        $this->hasOne('Location as Location', array('local' => 'id'));
    }
}
