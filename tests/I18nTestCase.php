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
 * Doctrine_I18n_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_I18n_TestCase extends Doctrine_UnitTestCase
{

    public function prepareData()
    { }

    public function prepareTables()
    {
        $this->tables = array();

        parent::prepareTables();
    }

    public function testTranslationTableGetsExported()
    {
    	$this->conn->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_ALL);
    	
    	$this->assertTrue(Doctrine::EXPORT_ALL & Doctrine::EXPORT_TABLES);
        $this->assertTrue(Doctrine::EXPORT_ALL & Doctrine::EXPORT_CONSTRAINTS);
        $this->assertTrue(Doctrine::EXPORT_ALL & Doctrine::EXPORT_PLUGINS);

        $sql = $this->conn->export->exportClassesSql(array('I18nTest'));

        foreach ($sql as $query) {
            $this->conn->exec($query);
        }
    }

    public function testTranslationTableIsInitializedProperly()
    {
        $i = new I18nTest();

        $i->name = 'some name';
        $i->title = 'some title';
        $this->assertEqual($i->Translation->getTable()->getComponentName(), 'I18nTestTranslation');


        $i->Translation['FI']->name = 'joku nimi';
        $i->Translation['FI']->title = 'joku otsikko';
        $i->Translation['FI']->lang = 'FI';

        $i->save();

        $this->conn->clear();

        $t = Doctrine_Query::create()->from('I18nTestTranslation')->fetchOne();

        $this->assertEqual($t->name, 'joku nimi');
        $this->assertEqual($t->title, 'joku otsikko');
        $this->assertEqual($t->lang, 'FI');

    }

    public function testDataFetching()
    {
        $i = Doctrine_Query::create()->from('I18nTest i')->innerJoin('i.Translation t INDEXBY t.lang')->fetchOne(array(), Doctrine::HYDRATE_ARRAY);

        $this->assertEqual($i['name'], 'some name');
        $this->assertEqual($i['title'], 'some title');

        $this->assertEqual($i['Translation']['FI']['name'], 'joku nimi');
        $this->assertEqual($i['Translation']['FI']['title'], 'joku otsikko');
        $this->assertEqual($i['Translation']['FI']['lang'], 'FI');
    }
}
