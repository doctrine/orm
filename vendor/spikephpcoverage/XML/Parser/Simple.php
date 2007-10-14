<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Stephan Schmidt <schst@php-tools.net>                        |
// +----------------------------------------------------------------------+
//
// $Id: Simple.php,v 1.6 2005/03/25 17:13:10 schst Exp $

/**
 * Simple XML parser class.
 *
 * This class is a simplified version of XML_Parser.
 * In most XML applications the real action is executed,
 * when a closing tag is found.
 *
 * XML_Parser_Simple allows you to just implement one callback
 * for each tag that will receive the tag with its attributes
 * and CData
 *
 * @category XML
 * @package XML_Parser
 * @author  Stephan Schmidt <schst@php-tools.net>
 */

/**
 * built on XML_Parser
 */
require_once 'XML/Parser.php';

/**
 * Simple XML parser class.
 *
 * This class is a simplified version of XML_Parser.
 * In most XML applications the real action is executed,
 * when a closing tag is found.
 *
 * XML_Parser_Simple allows you to just implement one callback
 * for each tag that will receive the tag with its attributes
 * and CData.
 *
 * <code>
 * require_once '../Parser/Simple.php';
 *
 * class myParser extends XML_Parser_Simple
 * {
 *     function myParser()
 *     {
 *        $this->XML_Parser_Simple();
 *      }
 * 
 *    function handleElement($name, $attribs, $data)
 *     {
 *         printf('handle %s<br>', $name);
 *     }
 * }
 * 
 * $p = &new myParser();
 * 
 * $result = $p->setInputFile('myDoc.xml');
 * $result = $p->parse();
 * </code>
 *
 * @category XML
 * @package XML_Parser
 * @author  Stephan Schmidt <schst@php-tools.net>
 */
class XML_Parser_Simple extends XML_Parser
{
   /**
    * element stack
    *
    * @access   private
    * @var      array
    */
    var $_elStack = array();

   /**
    * all character data
    *
    * @access   private
    * @var      array
    */
    var $_data = array();

   /**
    * element depth
    *
    * @access   private
    * @var      integer
    */
    var $_depth = 0;

    /**
     * Mapping from expat handler function to class method.
     *
     * @var  array
     */
    var $handler = array(
        'default_handler'                   => 'defaultHandler',
        'processing_instruction_handler'    => 'piHandler',
        'unparsed_entity_decl_handler'      => 'unparsedHandler',
        'notation_decl_handler'             => 'notationHandler',
        'external_entity_ref_handler'       => 'entityrefHandler'
    );
    
    /**
     * Creates an XML parser.
     *
     * This is needed for PHP4 compatibility, it will
     * call the constructor, when a new instance is created.
     *
     * @param string $srcenc source charset encoding, use NULL (default) to use
     *                       whatever the document specifies
     * @param string $mode   how this parser object should work, "event" for
     *                       handleElement(), "func" to have it call functions
     *                       named after elements (handleElement_$name())
     * @param string $tgenc  a valid target encoding
     */
    function XML_Parser_Simple($srcenc = null, $mode = 'event', $tgtenc = null)
    {
        $this->XML_Parser($srcenc, $mode, $tgtenc);
    }

    /**
     * inits the handlers
     *
     * @access  private
     */
    function _initHandlers()
    {
        if (!is_object($this->_handlerObj)) {
            $this->_handlerObj = &$this;
        }

        if ($this->mode != 'func' && $this->mode != 'event') {
            return $this->raiseError('Unsupported mode given', XML_PARSER_ERROR_UNSUPPORTED_MODE);
        }
        xml_set_object($this->parser, $this->_handlerObj);

        xml_set_element_handler($this->parser, array(&$this, 'startHandler'), array(&$this, 'endHandler'));
        xml_set_character_data_handler($this->parser, array(&$this, 'cdataHandler'));
        
        /**
         * set additional handlers for character data, entities, etc.
         */
        foreach ($this->handler as $xml_func => $method) {
            if (method_exists($this->_handlerObj, $method)) {
                $xml_func = 'xml_set_' . $xml_func;
                $xml_func($this->parser, $method);
            }
		}
    }

    /**
     * Reset the parser.
     *
     * This allows you to use one parser instance
     * to parse multiple XML documents.
     *
     * @access   public
     * @return   boolean|object     true on success, PEAR_Error otherwise
     */
    function reset()
    {
        $this->_elStack = array();
        $this->_data    = array();
        $this->_depth   = 0;
        
        $result = $this->_create();
        if ($this->isError( $result )) {
            return $result;
        }
        return true;
    }

   /**
    * start handler
    *
    * Pushes attributes and tagname onto a stack
    *
    * @access   private
    * @final
    * @param    resource    xml parser resource
    * @param    string      element name
    * @param    array       attributes
    */
    function startHandler($xp, $elem, &$attribs)
    {
        array_push($this->_elStack, array(
                                            'name'    => $elem,
                                            'attribs' => $attribs
                                        )
                  );
        $this->_depth++;
        $this->_data[$this->_depth] = '';
    }

   /**
    * end handler
    *
    * Pulls attributes and tagname from a stack
    *
    * @access   private
    * @final
    * @param    resource    xml parser resource
    * @param    string      element name
    */
    function endHandler($xp, $elem)
    {
        $el   = array_pop($this->_elStack);
        $data = $this->_data[$this->_depth];
        $this->_depth--;

        switch ($this->mode) {
            case 'event':
                $this->_handlerObj->handleElement($el['name'], $el['attribs'], $data);
                break;
            case 'func':
                $func = 'handleElement_' . $elem;
                if (strchr($func, '.')) {
                    $func = str_replace('.', '_', $func);
                }
                if (method_exists($this->_handlerObj, $func)) {
                    call_user_func(array(&$this->_handlerObj, $func), $el['name'], $el['attribs'], $data);
                }
                break;
        }
    }

   /**
    * handle character data
    *
    * @access   private
    * @final
    * @param    resource    xml parser resource
    * @param    string      data
    */
    function cdataHandler($xp, $data)
    {
        $this->_data[$this->_depth] .= $data;
    }

   /**
    * handle a tag
    *
    * Implement this in your parser 
    *
    * @access   public
    * @abstract
    * @param    string      element name
    * @param    array       attributes
    * @param    string      character data
    */
    function handleElement($name, $attribs, $data)
    {
    }

   /**
    * get the current tag depth
    *
    * The root tag is in depth 0.
    *
    * @access   public
    * @return   integer
    */
    function getCurrentDepth()
    {
        return $this->_depth;
    }

   /**
    * add some string to the current ddata.
    *
    * This is commonly needed, when a document is parsed recursively.
    *
    * @access   public
    * @param    string      data to add
    * @return   void
    */
    function addToData( $data )
    {
        $this->_data[$this->_depth] .= $data;
    }
}
?>
