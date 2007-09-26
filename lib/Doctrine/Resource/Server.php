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
    public function __construct($name = null, $config = null)
    {
        if ($name) {
            $config['name'] = $name;
        }
        
        parent::__construct($config);
    }

    static public function getInstance($name, $config = null)
    {
        static $instance;
        
        if (!$instance) {
            $instance = new Doctrine_Resource_Server($name, $config);
        }
        
        return $instance;
    }
    
    public function validate($errors)
    {
        if (!empty($errors)) {
            throw new Doctrine_Resource_Exception(count($errors) . ' error(s) occurred: ' . implode('. ', $errors));
        } else {
            return true;
        }
    }
    
    public function validateOpenRecord($request)
    {
        $errors = array();
        
        if (!$request->has('model') || !$request->get('model')) {
            $errors[] = 'You must specify the model/class name you are deleting';
        }
        
        if (!$request->has('identifier') || !is_array($request->get('identifier'))) {
            $errors[] = 'You must specify an array containing the identifiers for the model you wish to delete';
        }
        
        return $errors;
    }
    
    public function validateSave($request)
    {
        $errors = $this->validateOpenRecord($request);
        
        if (!$request->has('data') || !$request->get('data')) {
            $errors[] = 'You must specify an containing the changed data to save to the model';
        }
        
        return $errors;
    }
    
    public function executeSave($request)
    {
        $model = $request->get('model');
        $data = $request->get('data');
        $identifier = $request->get('identifier');
        
        $table = Doctrine_Manager::getInstance()->getTable($model);
        
        $existing = true;
        foreach ($identifier as $key => $value) {
            if (!$value) {
                $existing = false;
            }
        }
        
        if ($existing) {
            $record = $table->find($identifier);
        } else {
            $record = new $model();
        }
        
        $record->fromArray($data);
        $record->save();
        
        return $record->toArray(true, true);
    }
    
    public function validateDelete($request)
    {
        return $this->validateOpenRecord($request);
    }
    
    public function executeDelete($request)
    {
        $model = $request->get('model');
        $identifier = $request->get('identifier');
        
        $table = Doctrine_Manager::getInstance()->getTable($model);
        
        $record = $table->find($identifier);
        
        if ($record) {
            $record->delete();
        } else {
            throw new Doctrine_Resource_Exception('Record could not be deleted because it is not a valid record');
        }
    }
    
    public function validateQuery($request)
    {
        $errors = array();
        
        if (!$request->has('dql') || !$request->get('dql')) {
            $errors[] = 'You must specify a dql string in order to execute a query';
        }
        
        return $errors;
    }
    
    public function executeQuery($request)
    {
        $dql = $request->get('dql');
        $params = $request->get('params') ? $request->get('params'):array();
        
        $conn = Doctrine_Manager::connection();
        
        return $conn->query($dql, $params)->toArray(true, true);
    }
    
    public function validateLoad($request)
    {
        $errors = array();
        
        return $errors;
    }
    
    public function executeLoad($request)
    {
        $path = '/tmp/' . rand();
        
        $models = $this->getConfig('models') ? $this->getConfig('models'):array();
        
        $export = new Doctrine_Export_Schema();
        $export->exportSchema($path, 'xml', null, $models);
        
        $schema = Doctrine_Parser::load($path, 'xml');
        
        unlink($path);
        
        return $schema;
    }
    
    public function execute(array $r)
    {
        if (!isset($r['xml'])) {
            throw new Doctrine_Resource_Exception('You must specify an xml string in your request');
        }
        
        $requestArray = Doctrine_Parser::load($r['xml']);
        
        $request = new Doctrine_Resource_Request($requestArray);
        
        $funcName = 'execute' . Doctrine::classify($request->get('action'));
        
        if (method_exists($this, $funcName)) {
            $validateFuncName = 'validate' . Doctrine::classify($request->get('action'));
            
            $errors = $this->$validateFuncName($request);
            
            if ($this->validate($errors)) {
                $result = $this->$funcName($request);
                
                return Doctrine_Parser::dump($result, 'xml');
            }
        } else {
            throw new Doctrine_Resource_Exception('Unknown Doctrine Resource Server function');
        }
    }
    
    public function run($request)
    {
        try {
            $result = $this->execute($request);
            
            echo $result;
        } catch(Exception $e) {
            echo $this->exception($e);
        }
    }
    
    public function exception($e)
    {
        $error = array('error' => $e->getMessage());
        
        return Doctrine_Parser::dump($error);
    }
}