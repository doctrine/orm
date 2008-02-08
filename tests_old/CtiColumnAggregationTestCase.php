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
 * Doctrine_CtiColumnAggregation_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_CtiColumnAggregation_TestCase extends Doctrine_UnitTestCase 
{

}
abstract class CTICAAbstractBase extends Doctrine_Record
{ }
class CTICATestParent1 extends CTICAAbstractBase
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
    }
}
class CTICATestParent2 extends CTICATestParent1
{
    public function setTableDefinition()
    {
    	parent::setTableDefinition();

        $this->hasColumn('verified', 'boolean', 1);
        $this->hasColumn('type', 'integer', 2);
        
        $this->setSubclasses(array(
            'CTICATest'  => array('type' => 1),
            'CTICATest2' => array('type' => 2)
            ));

    }
}
class CTICATestParent3 extends CTICATestParent2
{
    public function setTableDefinition()
    {
        $this->hasColumn('added', 'integer');
    }
}
class CTICATestParent4 extends CTICATestParent3
{
    public function setTableDefinition()
    {
        $this->hasColumn('age', 'integer', 4);
    }
}
class CTICATest extends CTICATestParent4
{

}
class CTICATest2 extends CTICATestParent2
{
    public function setTableDefinition()
    {
        $this->hasColumn('updated', 'integer');
    }
}

class CTICATestOneToManyRelated extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('cti_id', 'integer');
    }
    
    public function setUp()
    {
        $this->hasMany('CTICATestParent1', array('local' => 'cti_id', 'foreign' => 'id'));
    }
}
