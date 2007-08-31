<?php
/**
 * Parses for links to other documentation sections.
 *
 * @category Text
 * @package Text_Wiki
 * @author Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license LGPL
 * @version $Id$
 *
 */
class Text_Wiki_Parse_Doclink extends Text_Wiki_Parse {

    var $conf = array(
       'toc' => null
    );
    
    var $regex = '/\[doc ([a-z0-9-]+(?::[a-z0-9-]+)*)(?: ([^\n\]]*))?]/';

    function process(&$matches)
    {
        $toc = $this->getConf('toc');
        
        if ($toc instanceof Sensei_Doc_Toc) {
            $section = $toc->findByPath($matches[1]);
        }

        if (isset($section)) {
            $options = array();
             
            $options['path'] = $matches[1];
            
            if (isset($matches[2])) { 
                $options['text'] = $matches[2];
            } else {
                $options['text'] = $section->getIndex() . ' ' . $section->getName(true);
            }
            
            return $this->wiki->addToken($this->rule, $options);
            
        } else {
            return $matches[0];
        }

    }
}
