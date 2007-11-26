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
     * __construct
     *
     * @param Doctrine_Pager $pager     Doctrine_Pager object related to the pager layout
     * @param Doctrine_Pager_Range $pagerRange     Doctrine_Pager_Range object related to the pager layout
     * @param string $urlMask     URL to be assigned for each page
     * @return void
     */
    public function __construct($pager, $pagerRange, $urlMask)
    {
        $this->setPager($pager);
        $this->setPagerRange($pagerRange);

        $this->setTemplate('');
        $this->setSelectedTemplate('');
        $this->setSeparatorTemplate('');

        $this->setUrlMask($urlMask);
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
     * setPager
     *
     * Defines the Doctrine_Pager object related to the pager layout
     *
     * @param $pager       Doctrine_Pager object related to the pager range
     * @return void
     */
    protected function setPager($pager)
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
     * setPagerRange
     *
     * Defines the Doctrine_Pager_Range subclass object related to the pager layout
     *
     * @param $pagerRange       Doctrine_Pager_Range subclass object related to the pager range
     * @return void
     */
    protected function setPagerRange($pagerRange)
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
     * setUrlMask
     *
     * Defines the URL to be assigned for each page
     *
     * @param $urlMask       URL to be assigned for each page
     * @return void
     */
    protected function setUrlMask($urlMask)
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
            $options['page'] = $range[$i];
            $options['url'] = $this->parseUrl($options);

            $str .= $this->parseTemplate($options);

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
     * parseTemplate
     *
     * Process the template of a given page and return the processed template
     *
     * @param $options    Optional parameters to be applied in template and url mask
     * @return string  
     */
    protected function parseTemplate($options = array())
    {
        $str = '';

        if (isset($options['page']) && $options['page'] == $this->getPager()->getPage()) {
            $str = $this->getSelectedTemplate();
        }

        // Possible attempt where Selected == Template
        if ($str == '') {
            $str = $this->getTemplate();
        }

        $keys = array();
        $values = array();

        foreach ($options as $k => $v) {
            $keys[] = '{%'.$k.'}';
            $values[] = $v;
        }

        return str_replace($keys, $values, $str);
    }


    /**
     * parseUrl
     *
     * Process the url mask of a given page and return the processed url
     *
     * @param $options    Optional parameters to be applied in template and url mask
     * @return string  
     */
    protected function parseUrl($options = array())
    {
        $str = $this->getUrlMask();

        $keys = array();
        $values = array();

        foreach ($options as $k => $v) {
            $keys[] = '{%'.$k.'}';
            $values[] = $v;
        }

        return str_replace($keys, $values, $str);
    }
}

?>