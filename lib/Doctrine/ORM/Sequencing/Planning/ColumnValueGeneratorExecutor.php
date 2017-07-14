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
use Doctrine\ORM\Mapping\LocalColumnMetadata;
use Doctrine\ORM\Sequencing\Generator;

class ColumnValueGeneratorExecutor implements ValueGenerationExecutor
{
    /** @var LocalColumnMetadata */
    private $column;

    /** @var Generator */
    private $generator;

    public function __construct(LocalColumnMetadata $column, Generator $generator)
    {
        $this->column = $column;
        $this->generator = $generator;
    }

    public function execute(EntityManagerInterface $entityManager, /*object*/ $entity) : array
    {
        $value = $this->generator->generate($entityManager, $entity);

        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $convertedValue = $this->column->getType()->convertToPHPValue($value, $platform);

        return [$this->column->getColumnName() => $convertedValue];
    }

    public function isDeferred() : bool
    {
        return $this->generator->isPostInsertGenerator();
    }
}
