<?php 
/*
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
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
final class NativeQuery extends AbstractQuery
{
    private $_sql;

    /**
     * Sets the SQL of the query.
     *
     * @param string $sql
     * @return NativeQuery This query instance.
     */
    public function setSQL($sql)
    {
        $this->_sql = $sql;
        return $this;
    }

    /**
     * Gets the SQL query.
     *
     * @return mixed The built SQL query or an array of all SQL queries.
     * @override
     */
    public function getSQL()
    {
        return $this->_sql;
    }

    /**
     * {@inheritdoc}
     */
    protected function _doExecute()
    {
        $stmt = $this->_em->getConnection()->prepare($this->_sql);
        $params = $this->_params;
        foreach ($params as $key => $value) {
            if (isset($this->_paramTypes[$key])) {
                $stmt->bindValue($key, $value, $this->_paramTypes[$key]);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();

        return $stmt;
    }
}