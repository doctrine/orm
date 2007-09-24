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

/**
 * Doctrine_Resource_Request
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Resource_Request extends Doctrine_Resource_Params
{
    protected $_params = null;
    
    public function getConfig($key = null)
    {
        return Doctrine_Resource_Client::getInstance()->getConfig($key);
    }
    
    public function getParams()
    {
        if ($this->_params === null) {
            $this->_params = new Doctrine_Resource_Params();
        }
        
        return $this->_params;
    }
    
    public function get($key)
    {
        return $this->getParams()->get($key);
    }
    
    public function set($key, $value)
    {
        return $this->getParams()->set($key, $value);
    }
    
    public function execute()
    {
        $url  = $this->getConfig()->get('url');
        $url .= strstr($this->getConfig()->get('url'), '?') ? '&':'?';
        $url .= http_build_query($this->getParams()->getAll());
        
        $response = file_get_contents($url);
        
        return $response;
    }
    
    public function hydrate(array $array, $model, $passedKey = null)
    {
        $config = $this->getConfig();
        
        $collection = new Doctrine_Resource_Collection($model, $config);
        
        foreach ($array as $record) {
            $r = new Doctrine_Resource_Record($model, $config);
            
            foreach ($record as $key => $value) {
                if (is_array($value)) {
                    $r->set($key, $this->hydrate($value, $model, $config, $key));
                } else {
                    $r->set($key, $value);
                }
            }
        
            $collection[] = $r;
        }
        
        return $collection;
    }
}