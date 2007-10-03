<?php
/*
 *  $Id: Xml.php 1080 2007-02-10 18:17:08Z jwage $
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
/**
 * Doctrine_Parser_Xml
 *
 * @package     Doctrine
 * @subpackage  Parser
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Parser_Xml extends Doctrine_Parser
{
    public function arrayToXml($data, $rootNodeName = 'data', $xml = null)
    {
        if ($xml === null) {
            $xml = new SimpleXmlElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><$rootNodeName/>");
        }

        foreach($data as $key => $value)
        {
            if (is_array($value)) {
                $node = $xml->addChild($key);

                $this->arrayToXml($value, $rootNodeName, $node);
            } else {
                $value = htmlentities($value);

                $xml->addChild($key, $value);
            }
        }
      
      return $xml->asXML();
    }
    
    public function dumpData($array, $path = null)
    {
        $xml = $this->arrayToXml($array);
        
        if ($path) {
            return file_put_contents($path, $xml);
        } else {
            return $xml;
        }
    }
    
    public function loadData($path)
    {
        if (file_exists($path) && is_readable($path)) {
            $xmlString = file_get_contents($path);
        } else {
            $xmlString = $path;
        }
        
        $simpleXml = simplexml_load_string($xmlString);
        
        return $this->prepareData($simpleXml);
    }
    
    public function prepareData($simpleXml)
    {
        if ($simpleXml instanceof SimpleXMLElement) {
            $children = $simpleXml->children();
            $return = null;
        }

        foreach ($children as $element => $value) {
            if ($value instanceof SimpleXMLElement) {
                $values = (array) $value->children();

                if (count($values) > 0) {
                    $return[$element] = $this->prepareData($value);
                } else {
                    if (!isset($return[$element])) {
                        $return[$element] = (string) $value;
                    } else {
                        if (!is_array($return[$element])) {
                            $return[$element] = array($return[$element], (string) $value);
                        } else {
                            $return[$element][] = (string) $value;
                        }
                    }
                }
            }
        }

        if (is_array($return)) {
            return $return;
        } else {
            return array();
        }
    }
}