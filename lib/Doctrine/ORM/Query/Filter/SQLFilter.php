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

use Doctrine\DBAL\Connection;

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
    private $conn;

    private $parameters;

    final public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    final function setParameter($name, $value, $type)
    {
        // @todo: check for a valid type?
        $parameters[$name] = array('value' => $value, 'type' => $type);
    }

    final function getParameter($name)
    {
        if(!isset($parameters[$name])) {
            throw new \InvalidArgumentException("Parameter '" . $name . "' is does not exist.");
        }

        // @todo: espace the parameter
        return $paramaters[$name]['value'];
    }

    abstract function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias);
}
