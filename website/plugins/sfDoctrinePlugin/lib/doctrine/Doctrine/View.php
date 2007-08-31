<?php
/*
 *  $Id: View.php 1080 2007-02-10 18:17:08Z romanb $
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
 * Doctrine_View
 *
 * this class represents a database view
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 */
class Doctrine_View
{
    /**
     * SQL DROP constant
     */
    const DROP   = 'DROP VIEW %s';
    /**
     * SQL CREATE constant
     */
    const CREATE = 'CREATE VIEW %s AS %s';
    /**
     * SQL SELECT constant
     */
    const SELECT = 'SELECT * FROM %s';

    /**
     * @var string $name                the name of the view
     */
    protected $name;
    /**
     * @var Doctrine_Query $query       the DQL query object this view is hooked into
     */
    protected $query;
    /**
     * @var Doctrine_Connection $conn   the connection object
     */
    protected $conn;

    /**
     * constructor
     *
     * @param Doctrine_Query $query
     */
    public function __construct(Doctrine_Query $query, $viewName)
    {
        $this->name  = $viewName;
        $this->query = $query;
        $this->query->setView($this);
        $this->conn   = $query->getConnection();
    }
    /**
     * getQuery
     * returns the associated query object
     *
     * @return Doctrine_Query
     */
    public function getQuery()
    {
        return $this->query;
    }
    /**
     * getName
     * returns the name of this view
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * getConnection
     * returns the connection object
     *
     * @return Doctrine_Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }
    /**
     * create
     * creates this view
     *
     * @throws Doctrine_View_Exception
     * @return void
     */
    public function create()
    {
        $sql = sprintf(self::CREATE, $this->name, $this->query->getQuery());
        try {
            $this->conn->execute($sql);
        } catch(Doctrine_Exception $e) {
            throw new Doctrine_View_Exception($e->__toString());
        }
    }
    /**
     * drop
     * drops this view from the database
     *
     * @throws Doctrine_View_Exception
     * @return void
     */
    public function drop()
    {
        try {
            $this->conn->execute(sprintf(self::DROP, $this->name));
        } catch(Doctrine_Exception $e) {
            throw new Doctrine_View_Exception($e->__toString());
        }
    }
    /**
     * execute
     * executes the view
     * returns a collection of Doctrine_Record objects
     *
     * @return Doctrine_Collection
     */
    public function execute()
    {
        return $this->query->execute();
    }
    /**
     * getSelectSql
     * returns the select sql for this view
     *
     * @return string
     */
    public function getSelectSql()
    {
        return sprintf(self::SELECT, $this->name);
    }
}
