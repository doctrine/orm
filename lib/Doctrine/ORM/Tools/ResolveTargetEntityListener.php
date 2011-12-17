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

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Events\LoadClassMetadataEventArgs;

/**
 * ResolveTargetEntityListener
 *
 * Mechanism to overwrite interfaces or classes specified as association
 * targets.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.2
 */
class ResolveTargetEntityListener
{
    /**
     * @var array
     */
    private $resolveTargetEntities = array();

    /**
     * Add a target-entity class name to resolve to a new class name.
     *
     * @param string $originalEntity
     * @param string $newEntity
     * @param array $mapping
     * @return void
     */
    public function addResolveTargetEntity($originalEntity, $newEntity, array $mapping)
    {
        $mapping['targetEntity'] = ltrim($newEntity, "\\");
        $this->resolveTargetEntities[ltrim($originalEntity, "\\")] = $mapping;
    }

    /**
     * Process event and resolve new target entity names.
     *
     * @param LoadClassMetadataEventArgs $args
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $cm = $args->getClassMetadata();
        foreach ($cm->associationMappings as $assocName => $mapping) {
            if (isset($this->resolveTargetEntities[$mapping['targetEntity']])) {
                $this->remapAssociation($cm, $mapping);
            }
        }
    }

    private function remapAssocation($classMetadata, $mapping)
    {
        $newMapping = $this->resolveTargetEntities[$mapping['targetEntity']];
        $newMapping['fieldName'] = $mapping['fieldName'];
        unset($cm->associationMappings[$mapping['fieldName']]);

        switch ($mapping['type']) {
            case ClassMetadata::MANY_TO_MANY:
                $cm->mapManyToMany($newMapping);
                break;
            case ClassMetadata::MANY_TO_ONE:
                $cm->mapManyToOne($newMapping);
                break;
            case ClassMetadata::ONE_TO_MANY:
                $cm->mapOneToMany($newMapping);
                break;
            case ClassMetadata::ONE_TO_ONE:
                $cm->mapOneToOne($newMapping);
                break;
        }
    }
}

