<?php
/*
 *  $Id: Module.php 1080 2007-02-10 18:17:08Z romanb $
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
 * Doctrine_Connection_Module
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Connection_Module
{
    /**
     * @var Doctrine_Connection $conn       Doctrine_Connection object, every connection
     *                                      module holds an instance of Doctrine_Connection
     */
    protected $conn;
    /**
     * @var string $moduleName              the name of this module
     */
    protected $moduleName;
    /**
     * @param Doctrine_Connection $conn     Doctrine_Connection object, every connection
     *                                      module holds an instance of Doctrine_Connection
     */
    public function __construct($conn = null)
    {
        if ( ! ($conn instanceof Doctrine_Connection)) {
            $conn = Doctrine_Manager::getInstance()->getCurrentConnection();
        }
        $this->conn = $conn;

        $e = explode('_', get_class($this));

        $this->moduleName = $e[1];
    }
    /**
     * getConnection
     * returns the connection object this module uses
     *
     * @return Doctrine_Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }
    /**
     * getModuleName
     * returns the name of this module
     *
     * @return string       the name of this module
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }
}
