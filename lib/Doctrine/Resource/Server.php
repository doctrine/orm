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
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Resource_Server extends Doctrine_Resource
{
    public $config = array();
    public $format = 'xml';
    
    public function __construct($config)
    {
        $this->config = array_merge($config, $this->config);
    }
    
    public function executeSave($request)
    {
        $model = $request['model'];
        $data = $request['data'];
        
        $record = new $model();
        $record->fromArray($data);
        $record->save();
        
        return $record->toArray(true, true);
    }
    
    public function executeQuery($request)
    {
        $dql = $request['dql'];
        $params = isset($request['params']) ? $request['params']:array();
        
        $conn = Doctrine_Manager::connection();
        
        return $conn->query($dql, $params)->toArray(true, true);
    }
    
    public function execute($request)
    {
        if (!isset($request['type'])) {
            throw new Doctrine_Resource_Exception('You must specify a request type: query or save');
        }
        
        $format = isset($request['format']) ? $request['format']:'xml';
        $type = $request['type'];
        $funcName = 'execute' . Doctrine::classify($type);
        
        $result = $this->$funcName($request);
        
        return Doctrine_Parser::dump($result, $format);
    }
    
    public function run($request)
    {
        echo $this->execute($request);
    }
}
