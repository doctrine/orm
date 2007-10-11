<?php
/*
 *  $Id: Config.php 2753 2007-10-07 20:58:08Z Jonathan.Wage $
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
 * Doctrine_Config
 *
 * Class used to simplify the setup and configuration of a doctrine implementation.
 * 
 * @package     Doctrine
 * @subpackage  Config
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2753 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Config
{
    protected $connections = array();
    protected $cliConfig = array();
    
    /**
     * addConnection
     *
     * @param string $adapter 
     * @param string $name 
     * @return void
     */
    public function addConnection($adapter, $name = null)
    {
        $connections[] = Doctrine_Manager::getInstance()->openConnection($adapter, $name);
    }
    
    /**
     * bindComponent
     *
     * @param string $modelName 
     * @param string $connectionName 
     * @return void
     */
    public function bindComponent($modelName, $connectionName)
    {
        return Doctrine_Manager::getInstance()->bindComponent($modelName, $connectionName);
    }
    
    /**
     * setAttribute
     *
     * @param string $key 
     * @param string $value 
     * @return void
     */
    public function setAttribute($key, $value)
    {
        foreach ($this->connections as $connection) {   
            $connection->setAttribute($key, $value);
        }
    }
    
    /**
     * addCliConfig
     *
     * @param string $key 
     * @param string $value 
     * @return void
     */
    public function addCliConfig($key, $value)
    {
        $this->cliConfig[$key] = $value;
    }
    
    /**
     * getCliConfig
     *
     * @return void
     */
    public function getCliConfig()
    {
       return $this->cliConfig;
    }
}