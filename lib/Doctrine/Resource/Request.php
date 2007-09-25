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
    
    public function execute()
    {
        $url  = $this->getConfig()->get('url');
        $data = array('type' => $this->get('type'), 'format' => $this->getConfig()->get('format'), 'data' => Doctrine_Parser::dump($this->getAll(), $this->getConfig()->get('format')));
        
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Doctrine_Resource_Exception('Request failed');
        }
        
        curl_close($ch);
        
        $array = array();
        
        if ($response) {
            $array = Doctrine_Parser::load($response, $this->getConfig()->get('format'));
        }
        
        if (isset($array['error'])) {
            throw new Doctrine_Resource_Exception($array['error']);
        }
        
        return $array;
    }
    
    public function hydrate(array $array, $model, $records = array())
    {
        $collection = new Doctrine_Resource_Collection($model);
        
        foreach ($array as $recordKey => $record) {
            if (isset($records[$recordKey])) {
                $r = $records[$recordKey];
            } else {
                $r = new $model(false);
            }
        
            foreach ($record as $key => $value) {
                if ($r->getTable()->hasRelation($key) && !empty($value)) {
                    $relation = $r->getTable()->getRelation($key);
                    
                    if ($relation['type'] === Doctrine_Relation::MANY) {
                        $relationCollection = $this->hydrate($value, $relation['class']);
                        $relationCollection->setParent($r);
                        
                        foreach ($relationCollection as $relationRecord) {
                            $relationTable = $relationRecord->getTable();
                            
                            if ($relation = $relationTable->getRelationByClassName($model)) {
                                if ($relation['type'] === Doctrine_Relation::ONE) {
                                    $relationRecord->set($relation['alias'], $r);
                                    $relationRecord->clearChanges();
                                } else {
                                    $coll = new Doctrine_Resource_Collection($relation['class']);
                                    $coll[] = $r;
                                    
                                    $relationRecord->set($relation['alias'], $coll);
                                }
                                
                                $relationRecord->clearChanges();
                            }
                        }
                        
                        $r->set($key, $relationCollection);
                    } else {
                        $relationRecord = $this->hydrate(array($value), $relation['class'])->getFirst();
                        $relationTable = $relationRecord->getTable();
                        
                        if ($relation = $relationTable->getRelationByClassName($model)) {
                            $relationRecord->set($relation['alias'], $r);
                            $relationRecord->clearChanges();
                        }
                        
                        $r->set($key, $relationRecord);
                    }
                } else if($r->getTable()->hasColumn($key)) {
                    $r->set($key, $value);
                }
                
                $r->clearChanges();
            }
        
            $collection[] = $r;
        }
        
        return $collection;
    }
}