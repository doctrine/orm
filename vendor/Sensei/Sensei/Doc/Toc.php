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
 * <http://sourceforge.net/projects/sensei>.
 */

/**
 * Sensei_Doc_Toc
 *
 * @package     Sensei_Doc
 * @category    Documentation
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL
 * @link        http://sourceforge.net/projects/sensei
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @version     $Revision$
 * @since       1.0
 */
class Sensei_Doc_Toc implements Countable
{
    /**
     * An empty (root) section that contains all other sections.
     *
     * @var Sensei_Doc_Section
     */
    private $_toc;

    
    /**
     * Constructs a new table of contents from a file
     *
     * @param string $filename   Name of the file that contains the section
     *                           structure.
     */
    public function __construct($filename)
    {
        $this->_toc = new Sensei_Doc_Section();
        $this->_toc->parse(dirname($filename), basename($filename));
    }
    
    /**
     * Finds the section that matches the given path.
     * 
     * The path consists of section names, where spaces are replaced by
     * underscores, and which separated by '/' (default). 
     *
     * @param string $path Path      
     * @param string $separator A string that separates section names in path. 
     * @return Sensei_Doc_Section|null A section that matches the given path, or
     * null if no matching section was found.
     */
    public function findByPath($path, $separator = ':')
    {
        $sectionPaths = explode($separator, $path);
        $currentSection = $this->_toc;
        
        foreach ($sectionPaths as $sectionPath) {
            
            $found = false;
            
            for ($i = 0; $i < $currentSection->count(); $i++) {
                if ($currentSection->getChild($i)->getPath(true, $separator) === $sectionPath) {
                    $currentSection = $currentSection->getChild($i);
                    $found = true;
                    break;
                }
            }
            
            if ( ! $found) {
                return null;
            }
        }
        
        return $currentSection;
    }
    
    public function findByIndex($index, $separator = '.')
    {
        $indexes = explode($separator, $index);
        $currentSection = $this->_toc;
        
        if (end($indexes) === '') {
            array_pop($indexes);
        }
        
        foreach ($indexes as $i) {
            try {
                $currentSection = $currentSection->getChild((int) $i - 1);
            } catch (Exception $e) {
                return null;
            }
        }
        
        return $currentSection;
    }
    
    /**
     * Returns a root section with the given index.
     *
     * @param int $index
     * @return Sensei_Doc_Section
     */
    public function getChild($index)
    {
        return $this->_toc->getChild($index);
    }
    
    /**
     * Returns the number of sections (excluding their subsections).
     *
     * @return int
     */
    public function count()
    {
        return $this->_toc->count();
    }
}