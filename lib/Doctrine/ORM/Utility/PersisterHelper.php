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

namespace Doctrine\ORM\Utility;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\Query\QueryException;

/**
 * The PersisterHelper contains logic to infer binding types which is used in
 * several persisters.
 *
 * @link   www.doctrine-project.org
 * @since  2.5
 * @author Jasper N. Brouwer <jasper@nerdsweide.nl>
 */
class PersisterHelper
{
    /**
     * @param string                 $columnName
     * @param ClassMetadata          $class
     * @param EntityManagerInterface $em
     *
     * @return Type
     *
     * @throws \RuntimeException
     */
    public static function getTypeOfColumn($columnName, ClassMetadata $class, EntityManagerInterface $em)
    {
        if (isset($class->fieldNames[$columnName])) {
            $fieldName = $class->fieldNames[$columnName];
            $property  = $class->getProperty($fieldName);

            switch (true) {
                case ($property instanceof FieldMetadata):
                    return $property->getType();

                // Optimization: Do not loop through all properties later since we can recognize to-one owning scenario
                case ($property instanceof ToOneAssociationMetadata):
                    // We know this is the owning side of a to-one because we found columns in the class (join columns)
                    foreach ($property->getJoinColumns() as $joinColumn) {
                        if ($joinColumn->getColumnName() !== $columnName) {
                            continue;
                        }

                        $targetClass = $em->getClassMetadata($property->getTargetEntity());

                        return self::getTypeOfColumn($joinColumn->getReferencedColumnName(), $targetClass, $em);
                    }

                    break;
            }
        }

        // iterate over association mappings
        foreach ($class->getProperties() as $association) {
            if (! ($association instanceof AssociationMetadata)) {
                continue;
            }

            // resolve join columns over to-one or to-many
            $targetClass = $em->getClassMetadata($association->getTargetEntity());

            if (! $association->isOwningSide()) {
                $association = $targetClass->getProperty($association->getMappedBy());
                $targetClass = $em->getClassMetadata($association->getTargetEntity());
            }

            $joinColumns = $association instanceof ManyToManyAssociationMetadata
                ? $association->getJoinTable()->getInverseJoinColumns()
                : $association->getJoinColumns()
            ;

            foreach ($joinColumns as $joinColumn) {
                if ($joinColumn->getColumnName() !== $columnName) {
                    continue;
                }

                return self::getTypeOfColumn($joinColumn->getReferencedColumnName(), $targetClass, $em);
            }
        }

        throw new \RuntimeException(sprintf(
            'Could not resolve type of column "%s" of class "%s"',
            $columnName,
            $class->getName()
        ));
    }
}
