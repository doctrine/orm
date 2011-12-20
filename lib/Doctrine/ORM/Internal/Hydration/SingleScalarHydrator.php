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

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\DBAL\Connection,
    Doctrine\ORM\NoResultException,
    Doctrine\ORM\NonUniqueResultException;

/**
 * Hydrator that hydrates a single scalar value from the result set.
 *
 * @since  2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class SingleScalarHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $data    = $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
        $numRows = count($data);

        if ($numRows === 0) {
            throw new NoResultException();
        }

        if ($numRows > 1 || count($data[key($data)]) > 1) {
            throw new NonUniqueResultException();
        }

        $cache  = array();
        $result = $this->gatherScalarRowData($data[key($data)], $cache);

        return array_shift($result);
    }
}