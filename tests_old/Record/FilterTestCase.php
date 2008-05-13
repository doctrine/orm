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
 * Doctrine_Record_Filter_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_Filter_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData()
    { }
    public function prepareTables()
    {
        $this->tables = array('CompositeRecord', 'RelatedCompositeRecord');
        
        parent::prepareTables();
    }
    public function testStandardFiltersThrowsExceptionWhenGettingUnknownProperties()
    {
        $u = new User();
        
        try {
            $u->unknown;
        
            $this->fail();
        } catch (Doctrine_Record_Exception $e) {
            $this->pass();
        }
    }

    public function testStandardFiltersThrowsExceptionWhenSettingUnknownProperties()
    {
        $u = new User();
        
        try {
            $u->unknown = 'something';
        
            $this->fail();
        } catch (Doctrine_Record_Exception $e) {
            $this->pass();
        }
    }

    public function testCompoundFilterSupportsAccessingRelatedComponentProperties()
    {
        $u = new CompositeRecord();
        
        try {
            $u->name    = 'someone';
            $u->address = 'something';

            $u->save();

            $this->assertEqual($u->name, 'someone');
            $this->assertEqual($u->address, 'something');
            $this->assertEqual($u->Related->address, 'something');
        } catch (Doctrine_Record_Exception $e) {
            $this->fail();
        }
    }
}
class CompositeRecord extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string');
        $class->hasOne('RelatedCompositeRecord as Related', array('local' => 'id', 'foreign' => 'foreign_id'));

    	$class->unshiftFilter(new Doctrine_Record_Filter_Compound(array('Related')));
    }
}
class RelatedCompositeRecord extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('address', 'string');
        $class->setColumn('foreign_id', 'integer');
    }
}
