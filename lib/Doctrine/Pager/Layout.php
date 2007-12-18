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

Doctrine::autoload('Doctrine_Pager_Range');

/**
 * Doctrine_Pager_Layout
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @package     Doctrine
 * @subpackage  Pager
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Pager_Layout
{
    /**
     * @var Doctrine_Pager $_pager      Doctrine_Pager object related to the pager layout
     */
    private $_pager;

    /**
     * @var Doctrine_Pager_Range $_pagerRange      Doctrine_Pager_Range object related to the pager layout
     */
    private $_pagerRange;

    /**
     * @var string $_template      Template to be applied for inactive pages
     *                             (and also active is selected template is not defined)
     */
    private $_template;

    /**
     * @var string $_selectedTemplate     Template to be applied for active page
     */
    private $_selectedTemplate;

    /**
     * @var string $_separatorTemplate     Separator template, applied between each page
     */
    private $_separatorTemplate;

    /**
     * @var string $_urlMask      URL to be assigned for each page. Masks are used as: {%var_name}
     */
    private $_urlMask;
    
    /**
     * @var array $_maskReplacements      Stores references of masks and their correspondent 
     *                                    (replaces defined masks with new masks or values)
     */
    private $_maskReplacements = array();


    /**
     * __construct
     *
     * @param Doctrine_Pager $pager     Doctrine_Pager object related to the pager layout
     * @param Doctrine_Pager_Range $pagerRange     Doctrine_Pager_Range object related to the pager layout
     * @param string $urlMask     URL to be assigned for each page
     * @return void
     */
    public function __construct($pager, $pagerRange, $urlMask)
    {
        $this->_setPager($pager);
        $this->_setPagerRange($pagerRange);
        $this->_setUrlMask($urlMask);

        $this->setTemplate('');
        $this->setSelectedTemplate('');
        $this->setSeparatorTemplate('');
    }


    /**
     * getPager
     *
     * Returns the Doctrine_Pager object related to the pager layout
     *
     * @return Doctrine_Pager        Doctrine_Pager object related to the pager range
     */
    public function getPager()
    {
        return $this->_pager;
    }


    /**
     * _setPager
     *
     * Defines the Doctrine_Pager object related to the pager layout
     *
     * @param $pager       Doctrine_Pager object related to the pager range
     * @return void
     */
    protected function _setPager($pager)
    {
        $this->_pager = $pager;
    }


    /**
     * getPagerRange
     *
     * Returns the Doctrine_Pager_Range subclass object related to the pager layout
     *
     * @return Doctrine_Pager_Range        Doctrine_Pager_Range subclass object related to the pager range
     */
    public function getPagerRange()
    {
        return $this->_pagerRange;
    }


    /**
     * _setPagerRange
     *
     * Defines the Doctrine_Pager_Range subclass object related to the pager layout
     *
     * @param $pagerRange       Doctrine_Pager_Range subclass object related to the pager range
     * @return void
     */
    protected function _setPagerRange($pagerRange)
    {
        $this->_pagerRange = $pagerRange;
        $this->getPagerRange()->setPager($this->getPager());
    }


    /**
     * getUrlMask
     *
     * Returns the URL to be assigned for each page
     *
     * @return string        URL to be assigned for each page
     */
    public function getUrlMask()
    {
        return $this->_urlMask;
    }


    /**
     * _setUrlMask
     *
     * Defines the URL to be assigned for each page
     *
     * @param $urlMask       URL to be assigned for each page
     * @return void
     */
    protected function _setUrlMask($urlMask)
    {
        $this->_urlMask = $urlMask;
    }


     /**
     * getTemplate
     *
     * Returns the Template to be applied for inactive pages 
     *
     * @return string        Template to be applied for inactive pages
     */
    public function getTemplate()
    {
        return $this->_template;
    }


    /**
     * setTemplate
     *
     * Defines the Template to be applied for inactive pages 
     * (also active page if selected template not defined)
     *
     * @param $template       Template to be applied for inactive pages
     * @return void
     */
    public function setTemplate($template)
    {
        $this->_template = $template;
    }


    /**
     * getSelectedTemplate
     *
     * Returns the Template to be applied for active page
     *
     * @return string        Template to be applied for active page
     */
    public function getSelectedTemplate()
    {
        return $this->_selectedTemplate;
    }


    /**
     * setSelectedTemplate
     *
     * Defines the Template to be applied for active page
     *
     * @param $selectedTemplate       Template to be applied for active page
     * @return void
     */
    public function setSelectedTemplate($selectedTemplate)
    {
        $this->_selectedTemplate = $selectedTemplate;
    }


    /**
     * getSeparatorTemplate
     *
     * Returns the Separator template, applied between each page
     *
     * @return string        Separator template, applied between each page
     */
    public function getSeparatorTemplate()
    {
        return $this->_separatorTemplate;
    }


    /**
     * setSeparatorTemplate
     *
     * Defines the Separator template, applied between each page
     *
     * @param $separatorTemplate       Separator template, applied between each page
     * @return void
     */ 
    public function setSeparatorTemplate($separatorTemplate)
    {
        $this->_separatorTemplate = $separatorTemplate;
    }
    
    
    /**
     * addMaskReplacement
     *
     * Defines a mask replacement. When parsing template, it converts replacement
     * masks into new ones (or values), allowing to change masks behavior on the fly
     *
     * @param $oldMask       Mask to be replaced
     * @param $newMask       Mask or Value that will be defined after replacement
     * @param $asValue       Optional value (default false) that if defined as true,
     *                       changes the bahavior of replacement mask to replacement
     *                       value
     * @return void
     */ 
    public function addMaskReplacement($oldMask, $newMask, $asValue = false)
    {
        $this->_maskReplacements[$oldMask] = array(
            'newMask' => $newMask,
            'asValue' => ($asValue === false) ? false : true
        );
    }
    
    
    /**
     * removeMaskReplacement
     *
     * Remove a mask replacement
     *
     * @param $oldMask       Replacement Mask to be removed
     * @return void
     */ 
    public function removeMaskReplacement($oldMask)
    {
        if (isset($this->_maskReplacements[$oldMask])) {
            $this->_maskReplacements[$oldMask] = null;
            unset($this->_maskReplacements[$oldMask]);
        }
    }
    
    
    /**
     * cleanMaskReplacements
     *
     * Remove all mask replacements
     *
     * @return void
     */ 
    public function cleanMaskReplacements()
    {
        $this->_maskReplacements = null;
        $this->_maskReplacements = array();
    }


    /**
     * display
     *
     * Displays the pager on screen based on templates and options defined
     *
     * @param $options    Optional parameters to be applied in template and url mask
     * @param $return     Optional parameter if you want to capture the output of this method call 
     *                    (Default value is false), instead of printing it
     * @return mixed      If you would like to capture the output of Doctrine_Pager_Layout::display(),
     *                    use the return  parameter. If this parameter is set to TRUE, this method 
     *                    will return its output, instead of printing it (which it does by default)
     */
    public function display($options = array(), $return = false)
    {
        $range = $this->getPagerRange()->rangeAroundPage();
        $str = '';

        // For each page in range
        for ($i = 0, $l = count($range); $i < $l; $i++) {
            // Define some optional mask values
            $options['page_number'] = $range[$i];
            $options['page'] = $range[$i]; // Handy assignment for URLs
            $options['url'] = $this->_parseUrl($options);

            $str .= $this->_parseTemplate($options);

            // Apply separator between pages
            if ($i < $l - 1) {
                $str .= $this->getSeparatorTemplate();
            }
        }

        // Possible wish to return value instead of print it on screen
        if ($return) {
            return $str;
        }

        echo $str;
    }

    /**
     * simply calls display, and returns the output.
     */
    public function __toString()
    {
      return $this->display(array(), true);
    }


    /**
     * _parseTemplate
     *
     * Process the template of a given page and return the processed template
     *
     * @param $options    Optional parameters to be applied in template and url mask
     * @return string  
     */
    protected function _parseTemplate($options = array())
    {
        $str = '';

        if (isset($options['page_number']) && $options['page_number'] == $this->getPager()->getPage()) {
            $str = $this->_parseMaskReplacements($this->getSelectedTemplate());
        }

        // Possible attempt where Selected == Template
        if ($str == '') {
            $str = $this->_parseMaskReplacements($this->getTemplate());
        }

        $replacements = array();
        
        foreach ($options as $k => $v) {
            $replacements['{%'.$k.'}'] = $v;
        }

        return strtr($str, $replacements);
    }


    /**
     * _parseUrl
     *
     * Process the url mask of a given page and return the processed url
     *
     * @param $options    Optional parameters to be applied in template and url mask
     * @return string
     */
    protected function _parseUrl($options = array())
    {
        $str = $this->_parseMaskReplacements($this->getUrlMask());

        $replacements = array();

        foreach ($options as $k => $v) {
            $replacements['{%'.$k.'}'] = $v;
        }

        return strtr($str, $replacements);
    }
    
    
    /**
     * _parseMaskReplacements
     *
     * Process the mask replacements, changing from to-be replaced mask with new masks/values
     *
     * @param $str    String to have masks replaced
     * @return string  
     */
    protected function _parseMaskReplacements($str)
    {
        $replacements = array();

        foreach ($this->_maskReplacements as $k => $v) {
            $replacements['{%'.$k.'}'] = ($v['asValue'] === true) ? $v['newMask'] : '{%'.$v['newMask'].'}';
        }

        return strtr($str, $replacements);
    }
}
