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

namespace Doctrine\ORM\Query;

/**
 * Encapsulates the resulting components from a DQL query parsing process that
 * can be serialized.
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class ParserResult
{
	protected $_sqlExecutor;
    protected $_resultSetMapping;

    public function __construct()
    {
        $this->_resultSetMapping = new ResultSetMapping;
    }

    /**
     * Gets the ResultSetMapping for the parsed query.
     * 
     * @return ResultSetMapping The result set mapping of the parsed query or NULL
     *                          if the query is not a SELECT query.
     */
    public function getResultSetMapping()
    {
        return $this->_resultSetMapping;
    }

    /**
     * Sets the ResultSetMapping of the parsed query.
     *
     * @param ResultSetMapping $rsm
     */
    public function setResultSetMapping(ResultSetMapping $rsm)
    {
        $this->_resultSetMapping = $rsm;
    }

    /**
     * @nodoc
     */
    public function setSqlExecutor(\Doctrine\ORM\Query\Exec\AbstractExecutor $executor)
    {
        $this->_sqlExecutor = $executor;
    }

    /**
     * @nodoc
     */
    public function getSqlExecutor()
    {
        return $this->_sqlExecutor;
    }
}