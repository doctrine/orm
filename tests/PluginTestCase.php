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
 * Doctrine_Plugin_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Plugin_TestCase extends Doctrine_UnitTestCase 
{

    public function prepareData()
    { }

    public function prepareTables()
    { }

    public function testNestedPluginsGetExportedRecursively()
    {
        $sql = $this->conn->export->exportClassesSql(array('Wiki'));
        
        $this->assertEqual($sql[0], 'CREATE TABLE wiki_translation_version (title VARCHAR(255), content VARCHAR(2147483647), lang VARCHAR(2), id INTEGER, version INTEGER, PRIMARY KEY(lang, id, version))');
        $this->assertEqual($sql[1], 'CREATE TABLE wiki_translation_index (keyword VARCHAR(200), field VARCHAR(50), position INTEGER, lang VARCHAR(2), id INTEGER, PRIMARY KEY(keyword, field, position, lang, id))');
        $this->assertEqual($sql[2], 'CREATE TABLE wiki_translation (title VARCHAR(255), content VARCHAR(2147483647), lang VARCHAR(2), id INTEGER, PRIMARY KEY(lang, id))');
        $this->assertEqual($sql[3], 'CREATE TABLE wiki (id INTEGER PRIMARY KEY AUTOINCREMENT)');
    }
}
class Wiki extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('title', 'string', 255);
        $this->hasColumn('content', 'string');
    }

    public function setUp()
    {
    	$options = array('fields' => array('title', 'content'));
        $auditLog = new Doctrine_Template_Versionable($options);
        $search = new Doctrine_Template_Searchable($options);
    	$i18n = new Doctrine_Template_I18n($options);
        
        $i18n->addChild($auditLog)
             ->addChild($search);

        $this->actAs($i18n);
    }
}
