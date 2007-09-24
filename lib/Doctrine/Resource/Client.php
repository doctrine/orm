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
 * Doctrine_Resource_Client
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
class Doctrine_Resource_Client extends Doctrine_Resource
{
    public $loadDoctrine = false;
    
    static public function getInstance($config = null)
    {
        static $instance;
        
        if (!$instance) {
            $instance = new Doctrine_Resource_Client($config);
            
            if ($instance->loadDoctrine === true) {
                $instance->loadDoctrine();
            }
        }
        
        return $instance;
    }
    
    public function loadDoctrine()
    {
        $path = '/tmp/' . md5(serialize($this->getConfig()));
        
        if (!file_exists($path)) {
            $schema = file_get_contents($path);
        } else {
            $request = new Doctrine_Resource_Request();
            $request->set('type', 'load');
            $request->set('format', $this->getConfig()->get('format'));
            
            $schema = $request->execute();
            
            if ($schema) {
                file_put_contents($path, $schema);
            }
        }
        
        if (file_exists($path) && $schema) {
            $import = new Doctrine_Import_Schema();
            $schema = $import->buildSchema($path, $this->getConfig()->get('format'));
            
            $this->getConfig()->set('schema', $schema);
        }
    }
    
    public function newQuery()
    {
        return new Doctrine_Resource_Query();
    }
    
    public function newRecord($model, $loadRelations = true)
    {
        return new Doctrine_Resource_Record($model, $loadRelations);
    }
    
    public function newCollection($model)
    {
        return new Doctrine_Resource_Collection($model);
    }
}