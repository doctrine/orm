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
 * Sensei_Doc_Section
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
class Sensei_Doc_Section implements Countable
{
    /**
     * Name of the section
     *
     * @var string
     */
    private $_name;
    
    /**
     * The index of this section among the subsections of its parent. The index 
     * ranges from 0 to the number of subsections the parent has minus one.
     *
     * @var int
     */
    private $_index;
    
    /**
     * Array containing the subsections of this section.
     *
     * @var array
     */
    private $_children = array();
    
    /**
     * The parent of this section.
     *
     * @var Sensei_Doc_Section
     */
    private $_parent;
    
    /**
     * Level of this section in section hierarchy.
     *
     * @var int
     */
    private $_level = 0;
    
    /**
     * Text contents of this section.
     *
     * @var string
     */
    private $_text = '';
    
       
    /**
     * Constructs a new section.
     *
     * @param string             $name    name of the section
     * @param Sensei_Doc_Section $parent  parent of the section
     */
    public function __construct($name = null, $parent = null)
    {
        $this->_name  = $name;
        $this->_parent = $parent;
        
        if ($parent !== null) {
            $this->_parent->addChild($this);
            $this->_level = $this->_parent->_level + 1;
        }
    }
    
    /**
     * Adds a subsection to this section.
     *
     * @param Sensei_Doc_Section $child
     */
    protected function addChild(Sensei_Doc_Section $child)
    {
        $child->_index = count($this);
        $this->_children[] = $child;
    }
    
    /**
     * Returns the index of this section.
     *
     * @param string $separator
     * @return string
     */
    public function getIndex($separator = '.')
    {
        if ($this->_parent->_name !== null) {
            return $this->_parent->getIndex($separator) . $separator . ($this->_index + 1);
        } else {
            return ($this->_index + 1);
        }
    }
    
    /**
     * Returns the path of this section.
     *
     * @param string $separator
     * @return string
     */
    public function getPath($short = false, $separator = ':')
    {
        if ( ! $short && ! $this->_parent->isRoot()) {
            return $this->_parent->getPath($short, $separator) . $separator . $this->getPath(true);
        } else {
            $patterns = array('/\s/', '/[^a-z0-9-]/');
            $replacements = array('-', '');
            
            $path = preg_replace($patterns, $replacements, strtolower($this->_name)); 
            
            return self::convertNameToPath($this->_name);
        }
    }
    
    /**
     * Returns the name of this section.
     *
     * @param boolean $full
     * @param string $separator
     * @return string
     */
    public function getName($full = false, $separator = ' - ')
    {
        if ($full &&  ! $this->_parent->isRoot()) {
            return $this->_parent->getName($full, $separator) . $separator . $this->_name;
        } else {
            return $this->_name;
        }
    }
    
    /**
     * Returns how many subsections this section has.
     *
     * @return int   number of subsections
     */
    public function count()
    {
        return count($this->_children);
    }
    
    /**
     * Returns the subsection that has the given index.
     * 
     * The index ranges from 0 to the number of subsections this section has
     * minus one.
     *
     * @param int $index    index of the subsection
     * @return Sensei_Doc_Section    The subsection with given index.
     */
    public function getChild($index)
    {
        return $this->_children[$index];
    }
    
    /**
     * Returns the parent of this section.
     *
     * @return Sensei_Doc_Section
     */
    public function getParent()
    {
        if ($this->_parent->isRoot()) {
            return null;
        } else {
            return $this->_parent;
        }
    }
    
    /**
     * Returns the next section.
     * 
     * If this section has subsections and their level is at most the specified
     * maximum level, the next section is the first subsection of this section.
     * 
     * If this section is not the last subsection of its parent and level of
     * this section is at most the specified maximum level, the next section is
     * the next section at the same level as this.
     * 
     * Otherwise the next section is the next section of the parent that is on 
     * the same level as the parent. 
     * 
     * @param int $maxLevel   The maximum level that the next section can have.
     *                        If maximum level is 0 (default), this parameter is
     *                        discarded. 
     * 
     * @return Sensei_Doc_Section|null  The next section, or null if not 
     *                                  available.
     */
    public function next($maxLevel = 0)
    {
        if ($this->isRoot()) {
            
            return null;
            
        } else {
            
            if ((!$maxLevel || ($this->_level < $maxLevel))
                && (count($this) > 0)) {
                return $this->getChild(0);
            }
            
            if ((!$maxLevel || ($this->_level <= $maxLevel) )
                && ($this->_index < count($this->_parent) - 1)) {
                return $this->_parent->getChild($this->_index + 1);
            }
            
            return $this->_parent->next($this->_parent->_level);
            
        }
    }
    
    /**
     * Returns the previous section.
     *
     * @return Sensei_Doc_Section
     */
    public function previous($maxLevel = 0)
    {
        if ($maxLevel > 0 && $this->_level > $maxLevel) {
            $previous = $this;
            while ($previous->_level > $maxLevel) {
                $previous = $previous->getParent();
            }
            return $previous;
        }
        
        if ($this->_index === 0) {
            return $this->getParent();
        } else {
            $previousSibling = $this->_parent->getChild($this->_index - 1);
            return $previousSibling->findLastChild($maxLevel);
        }
    }
    
    /**
     * Finds the last child or grand child of this section.
     * 
     * If this section has no children, this section is returned.
     *
     * @param int $maxLevel  Specifies the maximum level that the algorithm will
     *                       traverse to. If maximum level is 0 (default), the
     *                       algorithm will go as deep as possible.   
     * @return Sensei_Doc_Section
     */
    public function findLastChild($maxLevel = 0)
    {
        if ((!$maxLevel || $this->_level < $maxLevel) && count($this) > 0) {
            return $this->getChild(count($this) - 1)->findLastChild();
        } else {
            return $this;
        }
    }
    
    /**
     * Returns true, if this section is the root section.
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->_parent === null;
    }
    
    /**
     * Returns the level of this section in section hierarchy.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->_level;
    }
    
    /**
     * Returns the text contents of this section.
     *
     * @return string
     */
    public function getText()
    {
        return $this->_text;
    }
    
    public function parse($path, $filename)
    {
        $file = file($path . DIRECTORY_SEPARATOR . $filename);
        $current = $this;
        
        if ($this->isRoot()) {
            $path .= DIRECTORY_SEPARATOR . basename($filename, '.txt');
        }
        
        foreach ($file as $lineNum => $line) {
            
            // Checks if the line is a heading
            if (preg_match('/^(\+{1,6}) (.*)/', trim($line), $matches)) {
                
                $level = strlen($matches[1]);
                $heading = $matches[2];
                
                if (($level > $this->getLevel()) && ($level <= $current->getLevel() + 1)) {
                
	                // The current section did not have any text in this file.
	                // Let's assume that the text is defined in another file.
	                if (!$current->isRoot() && $current->_text === '') {
	                    
	                    $otherFilename = $current->getPath(false, DIRECTORY_SEPARATOR) . '.txt';
	                    
	                    if (($filename !== $otherFilename)
	                     && (file_exists($path . DIRECTORY_SEPARATOR . $otherFilename))) {
	                        $current->parse($path, $otherFilename);
	                    }
	                }
	                
	                $parent = $current;
	                
	                while ($parent->getLevel() >= $level) {
	                    $parent = $parent->_parent;
	                }
	                
	                $current = new Sensei_Doc_Section($heading, $parent);
	                
                } else {
                    
                    $format = 'Section has no direct parent, or level of the '
                            . 'heading is not greater than the level of the '
                            . 'file in "%s" on line %d';
                    
                    $message = sprintf($format, $filename, $lineNum);
                    
                    throw new Sensei_Exception($message);
                    
                }
               
            } else {
                
                if ($current->_text === '') {
                    if (trim($line) !== '') {
                        $current->_text = $line;
                    }
                } else {
                    $current->_text .= $line;
                }
                
            }
        }
        
        // The last section did not have any text in this file.
	    // Let's assume that the text is defined in another file.
	    if (!$current->isRoot() && $current->_text === '') {

	        $otherFilename = $current->getPath(false, DIRECTORY_SEPARATOR) . '.txt';
	        	                    
	        if (($filename !== $otherFilename)
	         && (file_exists($path . DIRECTORY_SEPARATOR . $otherFilename))) {
	            $current->parse($path, $otherFilename);
	        }
	    }
    }

    /**
     * Converts section name to section path.
     *
     * Section path is generated from section name by making section name
     * lowercase, replacing all whitespace with a dash and removing all
     * characters that are not a letter, a number or a dash.
     *
     * @param $name string  section name
     * @return section path
     */
    public static function convertNameToPath($name)
    {
        $patterns = array('/\s/', '/[^a-z0-9-]/');
        $replacements = array('-', '');
            
        return preg_replace($patterns, $replacements, strtolower($name)); 
    }
}
