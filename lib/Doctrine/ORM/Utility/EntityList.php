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

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\ORMInvalidArgumentException;

/**
 * EntityList contains functions that operation on a list of entities
 *
 * @since   2.5
 * @author  Oleg Namaka <avantprogger@gmail.com>
 */
class EntityList
{
    /**
     * Sorts an array of entities by order criteria. One level sorting is supported only
     *
     * Example usages:
     *
     * sort($entityList, array('name' => 'ASC', 'Email' => 'DESC');
     *
     * @param array                $entityList Entities to sort
     * @param array                $orderBy    Order by criteria
     * @param ClassMetadataFactory $factory    Class metadata factory
     *
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public static function sort(array &$entityList, array $orderBy, ClassMetadataFactory $factory)
    {
        if (count($entityList) < 2 || !count($orderBy)) {
            return;
        }
        $getValue = function ($entity, $fieldName) use ($factory) {
            $result = null;
            /** @var ClassMetadataInfo $class */
            /** @var ClassMetadataInfo $toOneClass */
            $class = $factory->getMetadataFor(get_class($entity));
            if ($class->hasField($fieldName)) {
                $result = $class->getFieldValue($entity, $fieldName);
            } else if ($class->isSingleValuedAssociation($fieldName)) {
                $toOne      = $class->getFieldValue($entity, $fieldName);
                $toOneClass = $factory->getMetadataFor(get_class($toOne));
                if ($toOneClass->isIdentifierComposite) {
                    throw ORMInvalidArgumentException::invalidSortCompositeIdentifier();
                }
                $result = $toOneClass->getSingleIdReflectionProperty()->getValue($toOne);
            }
            return $result;
        };

        usort(
            $entityList,
            function ($entityOne, $entityTwo) use ($orderBy, $factory, $getValue) {
                $sortDirections = array(
                    'ASC'  => SORT_ASC,
                    'DESC' => SORT_DESC
                );
                foreach ($orderBy as $name => $direction) {
                    if (!is_string($name)) {
                        throw new \InvalidArgumentException('String direction value is expected');
                    }
                    $direction = strtoupper($direction);
                    if (!in_array($direction, array_keys($sortDirections))) {
                        throw new \InvalidArgumentException('Sort direction value is invalid');
                    }
                    $direction = $sortDirections[$direction];

                    $valueOne = $getValue($entityOne, $name);
                    $valueTwo = $getValue($entityTwo, $name);

                    if (extension_loaded('intl')) {
                        $valueOne = \Normalizer::normalize($valueOne, \Normalizer::FORM_D);
                        $valueTwo = \Normalizer::normalize($valueTwo, \Normalizer::FORM_D);
                    }
                    if ($valueOne !== $valueTwo) {
                        return ($direction === SORT_ASC)
                            ? strnatcasecmp($valueOne, $valueTwo)
                            : strnatcasecmp($valueTwo, $valueOne);
                    }
                }
                return 0;
            }
        );
    }
}
