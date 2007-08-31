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
 * Sensei_Doc_Renderer_Xhtml
 *
 * @package     Sensei_Doc
 * @category    Documentation
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL
 * @link        http://sourceforge.net/projects/sensei
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @version     $Revision$
 * @since       1.0
 */
class Sensei_Doc_Renderer_Xhtml extends Sensei_Doc_Renderer
{
    /**
     * Available options
     * 
     * (Sensei_Doc_Section|null) section :
     *     Section to be rendered. If null all sections will be rendered.
     *
     * (string) url_prefix :
     *     All URLs pointing to sections will be prefixed with this. 
     */
    protected $_options = array(
        'section'    => null,
        'url_prefix' => ''
    );
    
    public function __construct(Sensei_Doc_Toc $toc, array $options = array())
    {
        parent::__construct($toc, $options);
        
        $this->_wiki->setRenderConf('xhtml', 'Doclink', 'url_callback', array(&$this, 'makeUrl'));
    }
    
    /**
     * Renders table of contents as nested unordered lists.
     * 
     * @return string  rendered table of contents
     */
    public function renderToc()
    {
        return $this->_renderToc($this->_toc);
    }

    /**
     * Renders table of contents recursively as nested unordered lists.
     *
     * @param $section Sensei_Doc_Toc|Sensei_Doc_Section
     * @return string  rendered table of contents
     */
    protected function _renderToc($section)
    {
        $output = '';
    
        if ($section instanceof Sensei_Doc_Toc) {
            $class = ' class="tree"';
        } elseif ($section !== $this->_options['section']) {
            $class = ' class="closed"';
        } else {
            $class = '';
        }
        
        $output .= '<ul' . $class . '>' . "\n";       
       
        for ($i = 0; $i < $section->count(); $i++) {
            $child = $section->getChild($i);
            
            $text = $child->getIndex() . ' ' . $child->getName();
            $href = $this->makeUrl($child);
            
            $output .= '<li><a href="' . $href . '">' . $text . '</a>';
            
            if ($child->count() > 0) {
                $output .= "\n";
                $output .= $this->_renderToc($child);
            }
    
            $output .= '</li>' . "\n";
        }
    
        $output .= '</ul>' . "\n";
        
        return $output;
    }
    
    /**
     * Renders section defined by 'section' option. If 'section' option is not
     * set, renders all sections.
     *
     * @return string rendered sections
     */
    public function render()
    {
        $section = $this->_options['section'];
        
        if ($section instanceof Sensei_Doc_Section) {
        
            $content = $this->_renderSection($section);
        
        } else {

            // No section was set, so let's render all sections            
            $content = '';
            for ($i = 0; $i < count($this->_toc); $i++) {
                $content .= $this->_renderSection($this->_toc->getChild($i));
            }
        }
        
        $output = $this->_options['template'];
        
        $output = str_replace('%TITLE%', $this->_options['title'], $output);
        $output = str_replace('%AUTHOR%', $this->_options['author'], $output);
        $output = str_replace('%SUBJECT%', $this->_options['subject'], $output);
        $output = str_replace('%KEYWORDS%', $this->_options['keywords'], $output);
        $output = str_replace('%TOC%', $this->renderToc(), $output);
        $output = str_replace('%CONTENT%', $content, $output);
        
        return $output;
    }
    
    /**
     * Renders a sections and its children
     *
     * @param $section Sensei_Doc_Section  section to be rendered
     * @return string  rendered sections
     */
    protected function _renderSection(Sensei_Doc_Section $section)
    {
        $output = '';
        
        $title = $section->getIndex() . ' ' . $section->getName();
        $level = $section->getLevel();
        
        if ($level === 1) {
            $class = ' class="chapter"';
            $title = 'Chapter ' . $title;
        } else {
            $class = ' class="section"';
        }
        
        $output .= '<div' . $class .'>' . "\n";
        
        $output .= "<h$level>";
        
        if ( ! ($this->_options['section'] instanceof Sensei_Doc_Section)
        || ($level > $this->_options['section']->getLevel())) {
            $anchor = $this->makeAnchor($section);
            $output .= '<a href="#' . $anchor . '" id="' . $anchor . '">';
            $output .= $title . '</a>';
        } else {
            $output .= $title;
        }
        
        $output .= "</h$level>";
        
        // Transform section contents from wiki syntax to XHTML
        $output .= $this->_wiki->transform($section->getText());
        
        // Render children of this section recursively
        for ($i = 0; $i < count($section); $i++) {
            $output .= $this->_renderSection($section->getChild($i));
        }
        
        $output .= '</div>' . "\n";
        
        return $output;       
    }
    
    public function makeUrl(Sensei_Doc_Section $section)
    {
        $url = $this->_options['url_prefix'];
        
        if ($this->_options['section'] instanceof Sensei_Doc_Section) {
            $path = $section->getPath();
            $level = $this->_options['section']->getLevel();
            $url .= implode(':', array_slice(explode(':', $path), 0, $level));
        }
        
        $anchor = $this->makeAnchor($section);
        if ($anchor !== '') {
            $url .= '#' . $anchor;
        }
        
        return $url;
    }
    
    public function makeAnchor(Sensei_Doc_Section $section)
    {
        $path = $section->getPath();
        
        if ($this->_options['section'] instanceof Sensei_Doc_Section) {
            $level = $this->_options['section']->getLevel();
            return implode(':', array_slice(explode(':', $path), $level));
        } else {
            return $path;
        }
    }
}
