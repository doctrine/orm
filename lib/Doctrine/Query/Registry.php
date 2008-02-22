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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Query_Registry
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Registry
{
    protected $_queries = array();

    public function add($key, $query)
    {
    	if (strpos($key, '/') === false) {
            $this->_queries[$key] = $query;
        } else {
            // namespace found
            
            $e = explode('/', $key);

            $this->_queries[$e[0]][$e[1]] = $query;
        }
    }
    
    public function get($key, $namespace = null)
    {
        if (isset($namespace)) {
            if ( ! isset($this->_queries[$namespace][$key])) {
                throw new Doctrine_Query_Registry_Exception('A query with the name ' . $namespace . '/' . $key . ' does not exist.');
            }
            $query = $this->_queries[$namespace][$key];
        } else {
            if ( ! isset($this->_queries[$key])) {
                try {
                    throw new Exception();
                } catch (Exception $e) {
                    echo $e->getTraceAsString() ."<br /><br />";
                }
                throw new Doctrine_Query_Registry_Exception('A query with the name ' . $key . ' does not exist.');
            }
            $query = $this->_queries[$key];
        }
        
        if ( ! ($query instanceof Doctrine_Query)) {
            $query = Doctrine_Query::create()->parseQuery($query);
        }
        
        return $query;
    }
}