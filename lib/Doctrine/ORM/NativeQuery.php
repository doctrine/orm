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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

/**
 * Represents a native SQL query.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class NativeQuery extends AbstractQuery
{
    private $_sql;

    /**
     * Initializes a new instance of the <tt>NativeQuery</tt> class that is bound
     * to the given EntityManager.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    /**
     * Sets the SQL of the query.
     *
     * @param string $sql
     */
    public function setSql($sql)
    {
        $this->_sql = $sql;
    }

    /**
     * Gets the SQL query/queries that correspond to this DQL query.
     *
     * @return mixed The built sql query or an array of all sql queries.
     * @override
     */
    public function getSql()
    {
        return $this->_sql;
    }

    /**
     * Executed the query.
     *
     * @param array $params
     * @return Statement  The Statement handle.
     * @override
     */
    protected function _doExecute(array $params)
    {
        // Assignments for Enums
        //$this->_setEnumParams($this->_parserResult->getEnumParams());

        // Converting parameters
        $params = $this->_prepareParams($params);

        // Executing the query and returning statement
        return $this->_em->getConnection()->execute($this->_sql, $params);
    }
}