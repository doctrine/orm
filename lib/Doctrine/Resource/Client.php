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
    
    static public function getInstance($url = null, $config = null)
    {
        static $instance;
        
        if (!$instance) {
            $instance = new Doctrine_Resource_Client($url, $config);
            
            if ($instance->loadDoctrine === true) {
                $instance->loadDoctrine();
            }
        }
        
        return $instance;
    }
    
    public function __construct($url, $config)
    {
        if ($url) {
            $config['url'] = $url;
        }
        
        parent::__construct($config);
    }
    
    public function loadDoctrine()
    {
        $path = '/tmp/' . $this->getClientKey();
        $classesPath = $path.'.classes.php';
        
        if (file_exists($path)) {
            $schema = file_get_contents($path);
        } else {
            $request = new Doctrine_Resource_Request();
            $request->set('action', 'load');
            
            $schema = $request->execute();
            
            if ($schema) {
                file_put_contents($path, Doctrine_Parser::dump($schema, Doctrine_Resource::FORMAT));
            }
        }
        
        if (file_exists($path) && $schema) {
            $import = new Doctrine_Import_Schema();
            $schema = $import->buildSchema($path, Doctrine_Resource::FORMAT);
            
            if (!file_exists($classesPath)) {
                $build = "<?php\n";
                foreach ($schema['schema'] as $className => $details) {
                    $build .= "class " . $className . " extends Doctrine_Resource_Record { protected \$_model = '".$className."'; public function __construct() { parent::__construct(\$this->_model); } }\n";
                    
                    $schema['schema'][$className]['relations'] = isset($schema['relations'][$className]) ? $schema['relations'][$className]:array();
                }
            
                file_put_contents($classesPath, $build);
            }
            
            require_once($classesPath);
            
            $this->getConfig()->set('schema', $schema);
        }
    }
    
    public function getClientKey()
    {
        return md5(Doctrine_Resource::FORMAT.serialize($this->getConfig()));
    }
    
    public function getTable($table)
    {
        static $instance;
        
        if(!isset($instance[$table])) {
            $instance[$table] = new Doctrine_Resource_Table($table);
        }
        
        return $instance[$table];
    }
    
    public function printSchema()
    {
        $schema = $this->getConfig('schema');
        
        echo '<h2>Schema</h2>';
        
        echo '<ul>';
        
        foreach ($schema['schema'] as $className => $info) {
            echo '<a name="'.$className.'"></a>';
            echo '<li><h3>'.$className.'</h3></li>';
            
            echo '<ul>';
            echo '<li>Columns';
            echo '<ul>';
            foreach ($info['columns'] as $columnName => $column)
            {
                echo '<li>' . $columnName;
                
                echo '<ul>';
                foreach ($column as $key => $value) {
                    if ($value) {
                        echo '<li>'.$key.': '.$value.'</li>';
                    }
                }
                echo '</ul>';
                
                echo '</li>';
            }
            echo '</ul>';
            echo '</li>';
            echo '</ul>';
            
            if (isset($info['relations']) && !empty($info['relations'])) {
                echo '<ul>';
                echo '<li>Relations';
                echo '<ul>';
                foreach ($info['relations'] as $relationName => $relation)
                {
                    echo '<li><a href="#'.$relation['class'].'">' . $relationName . '</a></li>';
                }
                echo '</ul>';
                echo '</li>';
                echo '</ul>';
            }
        }
        
        echo '</ul>';
        
        exit;
    }
}