<?php
/*
 *  $Id: Mock.php 1819 2007-06-25 17:48:44Z subzero2000 $
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
 * Doctrine_Adapter_Mock
 * This class is used for special testing purposes.
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Adapter
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1819 $
 */
class Doctrine_Adapter_Mock implements Doctrine_Adapter_Interface, Countable
{
    private $name;
    
    private $queries = array();
    
    private $exception = array();
    
    private $lastInsertIdFail = false;

    public function __construct($name = null) 
    {
        $this->name = $name;
    }
    public function getName() 
    {
        return $this->name;
    }
    public function pop() 
    {
        return array_pop($this->queries);
    }
    public function forceException($name, $message = '', $code = 0) 
    {
        $this->exception = array($name, $message, $code);
    }
    public function prepare($query)
    {
        $mock = new Doctrine_Adapter_Statement_Mock($this, $query);
        $mock->queryString = $query;
        
        return $mock;
    }
    public function addQuery($query)
    {
        $this->queries[] = $query;
    }
    public function query($query) 
    {
        $this->queries[] = $query;

        $e    = $this->exception;

        if( ! empty($e)) {
            $name = $e[0];

            $this->exception = array();

            throw new $name($e[1], $e[2]);
        }

        $stmt = new Doctrine_Adapter_Statement_Mock($this, $query);
        $stmt->queryString = $query;
        
        return $stmt;
    }
    public function getAll() 
    {
        return $this->queries;
    }
    public function quote($input) 
    {
        return "'" . addslashes($input) . "'";
    }
    public function exec($statement) 
    {
        $this->queries[] = $statement;

        $e    = $this->exception;

        if( ! empty($e)) {
            $name = $e[0];

            $this->exception = array();

            throw new $name($e[1], $e[2]);
        }

        return 0;
    }
    public function forceLastInsertIdFail($fail = true) 
    {
        if ($fail) {
            $this->lastInsertIdFail = true;
        } else {
            $this->lastInsertIdFail = false;
        }
    }
    public function lastInsertId()
    {
    	$this->queries[] = 'LAST_INSERT_ID()';
    	if ($this->lastInsertIdFail) {
            return null;
    	} else {
            return 1;
        }
    }
    public function count() 
    {
        return count($this->queries);	
    }
    public function beginTransaction()
    {
        $this->queries[] = 'BEGIN TRANSACTION';
    }
    public function commit()
    {
        $this->queries[] = 'COMMIT';
    }
    public function rollBack() 
    {
        $this->queries[] = 'ROLLBACK';
    }
    public function errorCode() 
    { }
    public function errorInfo()
    { }
    public function getAttribute($attribute) 
    {
        if($attribute == Doctrine::ATTR_DRIVER_NAME)
            return strtolower($this->name);
    }
    public function setAttribute($attribute, $value) 
    {
                                   	
    }
    public function sqliteCreateFunction()
    { }
}
