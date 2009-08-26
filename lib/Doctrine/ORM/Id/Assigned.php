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

namespace Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DoctrineException;

/**
 * Special generator for application-assigned identifiers (doesnt really generate anything).
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class Assigned extends AbstractIdGenerator
{
    /**
     * Returns the identifier assigned to the given entity.
     *
     * @param object $entity
     * @return mixed
     * @override
     */
    public function generate(EntityManager $em, $entity)
    {
        $class = $em->getClassMetadata(get_class($entity));
        $identifier = null;
        if ($class->isIdentifierComposite()) {
            $identifier = array();
            $idFields = $class->getIdentifierFieldNames();
            foreach ($idFields as $idField) {
                $identifier[] =
                $value = $class->getReflectionProperty($idField)->getValue($entity);
                if (isset($value)) {
                    $identifier[] = $value;
                }
            }
        } else {
            $value = $class->getReflectionProperty($class->getSingleIdentifierFieldName())
                    ->getValue($entity);
            if (isset($value)) {
                $identifier = array($value);
            }
        }

        if ( ! $identifier) {
            throw DoctrineException::entityMissingAssignedId($entity);
        }
        
        return $identifier;
    }
}