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
 * Doctrine_Resource_Server
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
class Doctrine_Resource_Server extends Doctrine_Resource
{
    static public function getInstance($config = null)
    {
        static $instance;
        
        if (!$instance) {
            $instance = new Doctrine_Resource_Server($config);
        }
        
        return $instance;
    }
    
    public function executeSave($request)
    {
        $model = $request->get('model');
        $data = $request->get('data');
        
        $table = Doctrine_Manager::getInstance()->getTable($model);
        $identifier = $table->getIdentifier();
        
        if (!is_array($identifier)) {
            $identifier = array($identifier);
        }
        
        $existing = true;
        $pks = array();
        foreach ($identifier as $name) {
            if (isset($data[$name]) && $data[$name]) {
                $pks[$name] = $data[$name];
            } else {
                $existing = false;
            }
        }
        
        if ($existing) {
            $record = $table->find($pks);
        } else {
            $record = new $model();
        }
        
        $record->fromArray($data);
        $record->save();
        
        return $record->toArray(true, true);
    }
    
    public function executeQuery($request)
    {
        $dql = $request->get('dql');
        $params = $request->get('params') ? $request->get('params'):array();
        
        $conn = Doctrine_Manager::connection();
        
        return $conn->query($dql, $params)->toArray(true, true);
    }
    
    public function executeLoad($request)
    {
        $path = '/tmp/' . rand() . '.' . $request->get('format');
        
        $models = $this->getConfig('models') ? $this->getConfig('models'):array();
        
        $export = new Doctrine_Export_Schema();
        $export->exportSchema($path, $request->get('format'), null, $models);
        
        $schema = Doctrine_Parser::load($path, $request->get('format'));
        
        unlink($path);
        
        return $schema;
    }
    
    public function execute(array $r)
    {
        if (!isset($r['data'])) {
            throw new Doctrine_Resource_Exception('You must specify a data xml string in your request');
        }                        
        
        $type = $r['type'];
        $format = isset($r['format']) ? $r['format']:'xml';
        $data = Doctrine_Parser::load($r['data'], $format);
        
        $funcName = 'execute' . Doctrine::classify($type);
        
        $requestObj = new Doctrine_Resource_Request($data);
        
        if (method_exists($this, $funcName)) {
            $result = $this->$funcName($requestObj);
        } else {
            throw new Doctrine_Resource_Exception('Unknown Doctrine Resource Server function');
        }
        
        if ($result) {
            return Doctrine_Parser::dump($result, $format);
        } else {
            return false;
        }
    }
    
    public function run($request)
    {
        echo $this->execute($request);
    }
}