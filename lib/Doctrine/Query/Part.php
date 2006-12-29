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
Doctrine::autoload("Doctrine_Access");
/**
 * Doctrine_Query_Part
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Query_Part extends Doctrine_Access
{
    /**
     * @var Doctrine_Query $query           the query object associated with this parser
     */
    protected $query;
    /**
     * @var string $name                    the name of this parser
     */
    protected $name;
    /**
     * @var array $parts
     */
    protected $parts = array();
    /**
     * @param Doctrine_Query $query         the query object associated with this parser
     */
    public function __construct(Doctrine_Query $query)
    {
        $this->query = $query;
    }
    /**
     * @return string $name                 the name of this parser
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @return Doctrine_Query $query        the query object associated with this parser
     */
    public function getQuery()
    {
        return $this->query;
    }
    /**
     * add
     *
     * @param string $value
     * @return void
     */
    public function add($value)
    {
        $method = "parse".$this->name;
        $this->query->$method($value);
    }

    public function get($name)
    { }
    public function set($name, $value)
    { }
}
