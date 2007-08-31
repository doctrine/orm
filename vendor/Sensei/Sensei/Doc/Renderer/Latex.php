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
 * Sensei_Doc_Renderer_Latex
 *
 * @package     Sensei_Doc
 * @category    Documentation
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL
 * @link        http://sourceforge.net/projects/sensei
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @version     $Revision$
 * @since       1.0
 */
class Sensei_Doc_Renderer_Latex extends Sensei_Doc_Renderer
{
    public function __construct(Sensei_Doc_Toc $toc, array $options = array())
    {
        parent::__construct($toc, $options);
    }
    
    protected function _mergeSections($section = null)
    {
        if ($section === null) {
            $section = $this->_toc;
        }
        
        $text = '';
        
        for ($i = 0; $i < count($section); $i++) {
            $child = $section->getChild($i);
            $text .= str_repeat('+', $child->getLevel()) . $child->getName() . "\n";
            $text .= $child->getText() . "\n";
            $text .= $this->_mergeSections($child);
        }
    
        return $text;
    }
    
    public function render()
    {
        $content = $this->_wiki->transform($this->_mergeSections(), 'Latex');
        
        $output = $this->_options['template'];
        
        $output = str_replace('%TITLE%', $this->_options['title'], $output);
        $output = str_replace('%AUTHOR%', $this->_options['author'], $output);
        $output = str_replace('%VERSION%', $this->_options['version'], $output);
        $output = str_replace('%SUBJECT%', $this->_options['subject'], $output);
        $output = str_replace('%KEYWORDS%', $this->_options['keywords'], $output);
        $output = str_replace('%CONTENT%', $content, $output);

        return $output;
    }    
}
