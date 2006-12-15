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
 * Doctrine_Hook
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hook { 
    /**
     * @var Doctrine_Query $query           the base query
     */
    private $query;
    /**
     * @var array $joins                    the optional joins of the base query
     */
    private $joins;

    /**
     * @param Doctrine_Query $query         the base query
     */
    public function __construct($query) {
        if(is_string($query)) {
            $this->query = new Doctrine_Query();
            $this->query->parseQuery($query);
        } elseif($query instanceof Doctrine_Query) {
            $this->query = $query;
        }
    }
    public function leftJoin($dql) {

    }
    public function innerJoin($dql) {
                                           	
    }
    public function hookWhere(array $params) {

    }
    public function hookOrderby(array $params) {

    }
    /**
     * @param integer $limit
     */
    public function hookLimit($limit) {

    }
    /**
     * @param integer $offset
     */
    public function hookOffset($offset) {
                                 	
    }
    public function setWhereHooks() {
                                    	
    }
    public function setOrderByHooks() {
                                    	
    }
}
