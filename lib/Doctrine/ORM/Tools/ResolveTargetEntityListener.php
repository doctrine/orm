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

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;

/**
 * ResolveTargetEntityListener
 *
 * Mechanism to overwrite interfaces or classes specified as association
 * targets.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.2
 */
class ResolveTargetEntityListener implements EventSubscriber
{
    /**
     * @var array[] indexed by original entity name
     */
    private $resolveTargetEntities = [];

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
            Events::onClassMetadataNotFound
        ];
    }

    /**
     * Adds a target-entity class name to resolve to a new class name.
     *
     * @param string $originalEntity
     * @param string $newEntity
     *
     * @return void
     */
    public function addResolveTargetEntity($originalEntity, $newEntity)
    {
        $this->resolveTargetEntities[ltrim($originalEntity, "\\")] = ltrim($newEntity, "\\");
    }

    /**
     * @param OnClassMetadataNotFoundEventArgs $args
     *
     * @internal this is an event callback, and should not be called directly
     *
     * @return void
     */
    public function onClassMetadataNotFound(OnClassMetadataNotFoundEventArgs $args)
    {
        if (array_key_exists($args->getClassName(), $this->resolveTargetEntities)) {
            $resolvedClassName = $this->resolveTargetEntities[$args->getClassName()];
            $resolvedMetadata  = $args->getObjectManager()->getClassMetadata($resolvedClassName);

            $args->setFoundMetadata($resolvedMetadata);
        }
    }

    /**
     * Processes event and resolves new target entity names.
     *
     * @param LoadClassMetadataEventArgs $args
     *
     * @return void
     *
     * @internal this is an event callback, and should not be called directly
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        /* @var $cm \Doctrine\ORM\Mapping\ClassMetadata */
        $class = $args->getClassMetadata();

        foreach ($class->discriminatorMap as $key => $className) {
            if (isset($this->resolveTargetEntities[$className])) {
                $targetEntity = $this->resolveTargetEntities[$className];

                $class->discriminatorMap[$key] = $targetEntity;
            }
        }

        foreach ($class->getProperties() as $association) {
            if ($association instanceof AssociationMetadata &&
                isset($this->resolveTargetEntities[$association->getTargetEntity()])) {
                $targetEntity = $this->resolveTargetEntities[$association->getTargetEntity()];

                $association->setTargetEntity($targetEntity);
            }
        }

        foreach ($this->resolveTargetEntities as $interface => $targetEntity) {
            if ($targetEntity === $class->getName()) {
                $args->getEntityManager()->getMetadataFactory()->setMetadataFor($interface, $class);
            }
        }
    }

    /**
     * @param ClassMetadata       $classMetadata
     * @param AssociationMetadata $association
     *
     * @return void
     */
    private function remapAssociation(ClassMetadata $classMetadata, AssociationMetadata $association)
    {
        $associationOverride = $this->resolveTargetEntities[$association->getTargetEntity()];

        $classMetadata->setAssociationOverride($associationOverride);
    }
}
