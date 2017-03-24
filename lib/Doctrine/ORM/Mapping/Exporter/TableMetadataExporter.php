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

use Doctrine\ORM\Mapping\TableMetadata;

class TableMetadataExporter implements Exporter
{
    const VARIABLE = '$table';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var TableMetadata $value */
        $variableExporter = new VariableExporter();
        $indentation      = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference  = $indentation . static::VARIABLE;
        $lines = [];

        $lines[] = $objectReference . ' = ' . $this->exportInstantiation($value);

        if (! empty($value->getSchema())) {
            $lines[] = $objectReference . '->setSchema("' . $value->getSchema() . '");';
        }

        foreach ($value->getIndexes() as $index) {
            $lines[] = $objectReference . '->addIndex(' . ltrim($variableExporter->export($index, $indentationLevel + 1)) . ');';
        }

        foreach ($value->getUniqueConstraints() as $uniqueConstraint) {
            $lines[] = $objectReference . '->addUniqueConstraint(' . ltrim($variableExporter->export($uniqueConstraint, $indentationLevel + 1)) . ');';
        }

        $lines[] = $objectReference . '->setOptions(' . ltrim($variableExporter->export($value->getOptions(), $indentationLevel + 1)) . ');';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param TableMetadata $metadata
     *
     * @return string
     */
    protected function exportInstantiation(TableMetadata $metadata) : string
    {
        return sprintf(
            'new Mapping\TableMetadata("%s");',
            $metadata->getName()
        );
    }
}