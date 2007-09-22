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
 * Doctrine_Resource_Record
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Resource_Record extends Doctrine_Record_Abstract implements Countable, IteratorAggregate
{
    public $data = array();
    public $config = array();
    public $model = null;
    
    public function __construct($model, $config)
    {
        $this->model = $model;
        $this->config = $config;
    }
    
    public function get($get)
    {
        if (!isset($this->data[$get])) {
            $this->data[$get] = null;
        }
        
        return $this->data[$get];
    }

    public function set($set, $value)
    {
        $this->data[$set] = $value;
    }
    
    public function count()
    {
        return count($this->data);
    }
    
    public function getIterator()
    {
        $data = $this->data;
        
        return new ArrayIterator($data);
    }
    
    public function newRequest($type)
    {
        $request = array();
        $request['format'] = isset($this->config['format']) ? $this->config['format']:'xml';
        $request['type'] = $type;
        $request['model'] = $this->model;
        
        return $request;
    }
    
    public function save()
    {
        $request = $this->newRequest('save');
        $request['data'] = $this->toArray();
        
        $response = Doctrine_Resource::request($this->config['url'], $request);
        
        $array = Doctrine_Parser::load($response, $request['format']);
        
        $resource = new Doctrine_Resource();
        $this->data = $resource->hydrate(array($array), $this->model, $this->config)->getFirst()->data;
    }
    
    public function toArray()
    {
        $array = array();
        
        foreach ($this->data as $key => $value) {
            if ($value instanceof Doctrine_Resource_Collection OR $value instanceof Doctrine_Resource_Record) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
}
