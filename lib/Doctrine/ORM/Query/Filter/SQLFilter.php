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

namespace Doctrine\ORM\Query\Filter;

use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\EntityManager;

/**
 * The base class that user defined filters should extend.
 *
 * Handles the setting and escaping of parameters.
 *
 * @author Alexander <iam.asm89@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @abstract
 */
abstract class SQLFilter
{
    private $em;

    private $parameters;

    final public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    final function setParameter($name, $value, $type)
    {
        // @todo: check for a valid type?
        $this->parameters[$name] = array('value' => $value, 'type' => $type);

        // Keep the parameters sorted for the hash
        ksort($this->parameters);

        // The filter collection of the EM is now dirty
        $this->em->getFilters()->setFiltersStateDirty();

        return $this;
    }

    final function getParameter($name)
    {
        if(!isset($this->parameters[$name])) {
            throw new \InvalidArgumentException("Parameter '" . $name . "' does not exist.");
        }

        return $this->em->getConnection()->quote($this->parameters[$name]['value'], $this->parameters[$name]['type']);
    }

    final function __toString()
    {
        return serialize($this->parameters);
    }

    /**
     * @return string The contstraint if there is one, empty string otherwise
     */
    abstract function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias);
}
