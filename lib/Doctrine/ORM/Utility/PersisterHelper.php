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

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
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
     * @param mixed                  $value
     * @param EntityManagerInterface $em
     *
     * @return mixed
     */
    public static function getValue($value, EntityManagerInterface $em)
    {
        if ( ! is_array($value)) {
            return self::getIndividualValue($value, $em);
        }

        $newValue = array();

        foreach ($value as $itemValue) {
            $newValue[] = self::getIndividualValue($itemValue, $em);
        }

        return $newValue;
    }

    /**
     * @param mixed                  $value
     * @param EntityManagerInterface $em
     *
     * @return mixed
     */
    private static function getIndividualValue($value, EntityManagerInterface $em)
    {
        if ( ! is_object($value) || ! $em->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value))) {
            return $value;
        }

        return $em->getUnitOfWork()->getSingleIdentifierValue($value);
    }

    /**
     * @param string                 $fieldName
     * @param ClassMetadata          $class
     * @param EntityManagerInterface $em
     *
     * @throws QueryException
     * @return string|null
     */
    public static function getTypeOfField($fieldName, ClassMetadata $class, EntityManagerInterface $em)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadataInfo $class */

        if (isset($class->fieldMappings[$fieldName])) {
            return $class->fieldMappings[$fieldName]['type'];
        }

        if ( ! isset($class->associationMappings[$fieldName])) {
            return null;
        }

        $assoc = $class->associationMappings[$fieldName];

        if (count($assoc['sourceToTargetKeyColumns']) > 1) {
            throw QueryException::associationPathCompositeKeyNotSupported();
        }

        $targetColumnName = $assoc['joinColumns'][0]['referencedColumnName'];
        $targetClass      = $em->getClassMetadata($assoc['targetEntity']);

        return self::getTypeOfColumn($targetColumnName, $targetClass, $em);
    }

    /**
     * @param string                 $columnName
     * @param ClassMetadata          $class
     * @param EntityManagerInterface $em
     *
     * @return string|null
     */
    public static function getTypeOfColumn($columnName, ClassMetadata $class, EntityManagerInterface $em)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadataInfo $class */

        if (isset($class->fieldNames[$columnName])) {
            $fieldName = $class->fieldNames[$columnName];

            if (isset($class->fieldMappings[$fieldName])) {
                return $class->fieldMappings[$fieldName]['type'];
            }

            return null;
        }

        foreach ($class->associationMappings as $assoc) {
            if (!isset($assoc['joinColumns'])) {
                continue;
            }

            foreach ($assoc['joinColumns'] as $joinColumn) {
                if ($joinColumn['name'] == $columnName) {
                    $targetColumnName = $joinColumn['referencedColumnName'];
                    $targetClass      = $em->getClassMetadata($assoc['targetEntity']);

                    return self::getTypeOfColumn($targetColumnName, $targetClass, $em);
                }
            }
        }

        return null;
    }
}
