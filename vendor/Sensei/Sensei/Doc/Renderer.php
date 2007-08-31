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
 * Sensei_Doc_Renderer
 *
 * @package     Sensei_Doc
 * @category    Documentation
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL
 * @link        http://sourceforge.net/projects/sensei
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @version     $Revision$
 * @since       1.0
 */
abstract class Sensei_Doc_Renderer
{
    protected $_wiki;
    protected $_toc;
    protected $_options = array();
    
    public function __construct(Sensei_Doc_Toc $toc, array $options = array())
    {
        $defaultOptions = array( 
            'title'    => 'Title',
            'author'   => 'Author',
            'version'  => '',
            'subject'  => 'Subject',
            'keywords' => 'key, word',
            'template' => ''
        );
        
        $this->_options = array_merge($defaultOptions, $this->_options);
            
        $this->setOptions($options);

        $this->_toc = $toc;
        
        $this->_wiki = Text_Wiki::singleton('Doc');
        $this->_wiki->setParseConf('Doclink', 'toc', $this->_toc);
    }
    
    abstract public function render();
    
    public function setOptions(array $options)
    {
        foreach ($options as $option => $value) {   
            $this->setOption($option, $value);
        }
    }
    
    public function setOption($option, $value)
    {
        if (is_string($option)) {
            if (array_key_exists($option, $this->_options)) {
                $this->_options[$option] = $value;
            } else {
                throw new Exception('Unknown option ' . $option . '.');
            }
        } else {
            throw new Exception('Option must be a string.');
        }
    }
    
    public function getOption($option)
    {
        return $this->_options[$option];
    }

}
