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

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing\Planning;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;

class CompositeValueGenerationPlan implements ValueGenerationPlan
{
    /** @var ClassMetadata */
    private $class;

    /** @var ValueGenerationExecutor[] */
    private $executors;

    /**
     * @param ValueGenerationExecutor[] $executors
     */
    public function __construct(ClassMetadata $metadata, array $executors)
    {
        $this->class = $metadata;
        $this->executors = $executors;
    }

    public function executeImmediate(EntityManagerInterface $entityManager, /*object*/ $entity): void
    {
        foreach ($this->executors as $executor) {
            if ($executor->isDeferred()) {
                continue;
            }

            $this->dispatchExecutor($executor, $entity, $entityManager);
        }
    }

    public function executeDeferred(EntityManagerInterface $entityManager, /*object*/ $entity): void
    {
        foreach ($this->executors as $executor) {
            if (! $executor->isDeferred()) {
                continue;
            }

            $this->dispatchExecutor($executor, $entity, $entityManager);
        }
    }

    private function dispatchExecutor(ValueGenerationExecutor $executor, /*object*/ $entity, EntityManagerInterface $entityManager): void
    {
        foreach ($executor->execute($entityManager, $entity) as $columnName => $value) {
            // TODO LocalColumnMetadata are currently shadowed and only exposed as FieldMetadata
            /** @var FieldMetadata $column */
            $column = $this->class->getColumn($columnName);
            $column->setValue($entity, $value);
        }
    }

    public function containsDeferred(): bool
    {
        foreach ($this->executors as $executor) {
            if ($executor->isDeferred()) {
                return true;
            }
        }

        return false;
    }
}
