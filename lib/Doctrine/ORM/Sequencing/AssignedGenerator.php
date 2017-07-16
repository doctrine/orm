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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Sequencing;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\Property;
use Doctrine\ORM\ORMException;

/**
 * Special generator for application-assigned sequencing (doesn't really generate anything).
 *
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class AssignedGenerator implements Generator
{
    /**
     * Returns the value assigned to the given property.
     *
     * {@inheritdoc}
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function generate(Property $property, EntityManager $em, $entity)
    {
        $value = $property->getValue($entity);

        if ($value === null) {
            throw ORMException::entityMissingAssignedIdForField($entity, $property->getName());
        }

        if ($property instanceof AssociationMetadata) {
            // NOTE: Single Columns as associated identifiers only allowed - this constraint it is enforced.
            return $em->getUnitOfWork()->getSingleIdentifierValue($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function isPostInsertGenerator()
    {
        return false;
    }
}
