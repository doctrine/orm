<?php

declare(strict_types = 1);

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

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Mapping\AssociationMetadata;

abstract class AssociationMetadataExporter implements Exporter
{
    const VARIABLE = '$property';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0): string
    {
        /** @var AssociationMetadata $value */
        $cacheExporter    = new CacheMetadataExporter();
        $variableExporter = new VariableExporter();
        $indentation      = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference  = $indentation . static::VARIABLE;
        $cascade          = $this->resolveCascade($value->getCascade());
        $lines            = [];

        $lines[] = $objectReference . ' = ' . $this->exportInstantiation($value);

        if (! empty($value->getCache())) {
            $lines[] = $cacheExporter->export($value->getCache(), $indentationLevel);
            $lines[] = null;
            $lines[] = $objectReference . '->setCache(' . $cacheExporter::VARIABLE . ');';
        }

        if (! empty($value->getMappedBy())) {
            $lines[] = $objectReference . '->setMappedBy("' . $value->getMappedBy() . '");';
        }

        if (! empty($value->getInversedBy())) {
            $lines[] = $objectReference . '->setInversedBy("' . $value->getInversedBy() . '");';
        }

        $lines[] = $objectReference . '->setSourceEntity("' . $value->getSourceEntity() . '");';
        $lines[] = $objectReference . '->setTargetEntity("' . $value->getTargetEntity() . '");';
        $lines[] = $objectReference . '->setFetchMode(Mapping\FetchMode::' . strtoupper($value->getFetchMode()) . '");';
        $lines[] = $objectReference . '->setCascade(' . $variableExporter->export($cascade, $indentationLevel + 1) . ');';
        $lines[] = $objectReference . '->setOwningSide(' . $variableExporter->export($value->isOwningSide(), $indentationLevel + 1) . ');';
        $lines[] = $objectReference . '->setOrphanRemoval(' . $variableExporter->export($value->isOrphanRemoval(), $indentationLevel + 1) . ');';
        $lines[] = $objectReference . '->setPrimaryKey(' . $variableExporter->export($value->isPrimaryKey(), $indentationLevel + 1) . ');';


        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array $cascade
     *
     * @return array
     */
    private function resolveCascade(array $cascade)
    {
        $resolvedCascade = ['remove', 'persist', 'refresh', 'merge', 'detach'];

        foreach ($resolvedCascade as $key => $value) {
            if (in_array($value, $cascade, true)) {
                continue;
            }

            unset($resolvedCascade[$key]);
        }

        return count($resolvedCascade) === 5
            ? ['all']
            : $resolvedCascade
        ;
    }

    /**
     * @param AssociationMetadata $metadata
     *
     * @return string
     */
    abstract protected function exportInstantiation(AssociationMetadata $metadata) : string;
}