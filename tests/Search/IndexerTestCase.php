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
 * Doctrine_Search_Indexer_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Search_Indexer_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData()
    { }
    public function prepareTables()
    {
        $this->tables = array('Doctrine_File', 'Doctrine_File_Index');
        
        parent::prepareTables();
    }

    public function testIndexexCanRecursivelyIndexDirectories()
    {
    	$profiler = new Doctrine_Connection_Profiler();
    	$this->conn->addListener($profiler);

        $indexer = new Doctrine_Search_Indexer();

        $indexer->indexDirectory(dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files');
    }
    
    public function testIndexerAddsFiles()
    {
        $files = Doctrine_Query::create()->from('Doctrine_File')->execute();

        $this->assertEqual($files->count(), 2);
    }

    public function testSearchingFiles()
    {
        $files = Doctrine_Query::create()->select('DISTINCT i.file_id')->from('Doctrine_File_Index i')
                 ->where('i.keyword = ?', array('database'))->execute(array(), Doctrine_Hydrate::HYDRATE_ARRAY);

        $this->assertEqual(count($files), 11);
    }
}
