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
 * Doctrine_Search
 *
 * @package     Doctrine
 * @subpackage  Search
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
class Doctrine_Search_File extends Doctrine_Search
{
    /**
     * constructor
     *
     * @param array $options    an array of plugin options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if ( ! isset($this->_options['resource'])) {
            $table = new Doctrine_Table('File', Doctrine_Manager::connection());

            $table->setColumn('url', 'string', 255, array('primary' => true));
        }

        if (empty($this->_options['fields'])) {
            $this->_options['fields'] = array('url', 'content');
        }

        $this->initialize($table);
    }
    public function buildRelation()
    {
    	
    }	
    /**
     * indexes given directory
     *
     * @param string $dir   the name of the directory to index
     * @return void
     */
    public function indexDirectory($dir)
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                RecursiveIteratorIterator::LEAVES_ONLY);
                                                
        foreach ($it as $file) {
            if (strpos($file, DIRECTORY_SEPARATOR . '.svn') !== false) {
                continue;
            }

            $this->updateIndex(array('url' => $file->getPathName(),
                                     'content' => file_get_contents($file)));
        }
    }
}
