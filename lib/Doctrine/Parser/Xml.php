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
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 */
class Doctrine_Parser_Xml extends Doctrine_Parser
{
    public function arrayToXml($array)
    {
        $this->text  = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>";
        
        $this->text .= $this->arrayTransform($array);
        
        return $this->text;
    }

    public function arrayTransform($array)
    {
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                $this->text .= "<$key>$value</$key>";
            } else {
                $this->text.="<$key>";
                $this->arrayTransform($value);
                $this->text.="</$key>";
            }
        }
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
        if ( !file_exists($path) OR !is_writeable($path) ) {
            throw new Doctrine_Parser_Exception('Xml file '. $path .' could not be read');
        }
        
        if ( ! ($xmlString = file_get_contents($path))) {
            throw new Doctrine_Parser_Exception('Xml file '. $path . ' is empty');
        }
        
        if (!file_exists($path) OR !is_readable($path) OR !($xmlString = file_get_contents($path))) {
            $xmlString = $path;
        }
        
        return simplexml_load_string($xmlString);
    }
}