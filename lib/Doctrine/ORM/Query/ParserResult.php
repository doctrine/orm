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

namespace Doctrine\ORM\Query;

/**
 * Doctrine_ORM_Query_ParserResult
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class ParserResult extends AbstractResult
{
    /**
     * The EntityManager.
     *
     * @var Doctrine_EntityManager
     */
    protected $_em;

    /**
     * Simple array of keys representing the fields used in query.
     *
     * @var array $_queryFields
     */
    protected $_queryFields = array();
    
    
    /**
     * Sets the Entity Manager.
     *
     * @param Doctrine_EntityManager $em The Entity Manager.
     */
    public function setEntityManager($em)
    {
        $this->_em = $em;
    }


    /**
     * Gets the Entity Manager.
     *
     * @return Doctrine_EntityManager
     */
    public function getEntityManager()
    {
        return $this->_em;
    }


    /**
     * @nodoc
     */
    public function setSqlExecutor(\Doctrine\ORM\Query\Exec\AbstractExecutor $executor)
    {
        $this->_data = $executor;
    }


    /**
     * @nodoc
     */
    public function getSqlExecutor()
    {
        return $this->_data;
    }


    /**
     * Defines the mapping fields.
     *
     * @param array $queryFields Query fields.
     */
    public function setQueryFields(array $queryFields)
    {
        $this->_queryFields = $queryFields;
    }


    /**
     * Sets the declaration for given field alias.
     *
     * @param string $fieldAlias The field alias to set the declaration to.
     * @param string $queryField Alias declaration.
     */
    public function setQueryField($fieldAlias, $queryField)
    {
        $this->_queryFields[$fieldAlias] = $queryField;
    }


    /**
     * Gets the mapping fields.
     *
     * @return array Query fields.
     */
    public function getQueryFields()
    {
        return $this->_queryFields;
    }


    /**
     * Get the declaration for given field alias.
     *
     * @param string $fieldAlias The field alias the retrieve the declaration from.
     * @return array Alias declaration.
     */
    public function getQueryField($fieldAlias)
    {
        if ( ! isset($this->_queryFields[$fieldAlias])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Unknown query field ' . $fieldAlias);
        }

        return $this->_queryFields[$fieldAlias];
    }


    /**
     * Whether or not this object has a declaration for given field alias.
     *
     * @param string $fieldAlias Field alias the retrieve the declaration from.
     * @return boolean True if this object has given alias, otherwise false.
     */
    public function hasQueryField($fieldAlias)
    {
        return isset($this->_queryFields[$fieldAlias]);
    }
}
