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
use Doctrine\ORM\Mapping\ClassMetadata;
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
     * @param string                 $fieldName
     * @param ClassMetadata          $class
     * @param EntityManagerInterface $em
     *
     * @return array
     *
     * @throws QueryException
     */
    public static function getTypeOfField($fieldName, ClassMetadata $class, EntityManagerInterface $em)
    {
        if (($property = $class->getProperty($fieldName)) !== null) {
            return [$property->getType()];
        }

        if ( ! isset($class->associationMappings[$fieldName])) {
            return [];
        }

        $assoc = $class->associationMappings[$fieldName];

        if (! $assoc['isOwningSide']) {
            return self::getTypeOfField($assoc['mappedBy'], $em->getClassMetadata($assoc['targetEntity']), $em);
        }

        $types       = [];
        $targetClass = $em->getClassMetadata($assoc['targetEntity']);
        $joinColumns = ($assoc['type'] & ClassMetadata::MANY_TO_MANY)
            ? $assoc['joinTable']->getJoinColumns()
            : $assoc['joinColumns'];

        foreach ($joinColumns as $joinColumn) {
            $types[] = self::getTypeOfColumn($joinColumn['referencedColumnName'], $targetClass, $em);
        }

        return $types;
    }

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

            return $class->getProperty($fieldName)->getType();
        }

        // iterate over association mappings
        foreach ($class->associationMappings as $assoc) {
            // resolve join columns over to-one or to-many
            $targetClass = $em->getClassMetadata($assoc['targetEntity']);
            $joinColumns = ($assoc['type'] & ClassMetadata::MANY_TO_MANY)
                ? $assoc['joinTable']->getJoinColumns()
                : $assoc['joinColumns'];

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
