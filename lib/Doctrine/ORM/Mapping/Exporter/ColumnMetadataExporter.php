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

use Doctrine\ORM\Mapping\ColumnMetadata;

class ColumnMetadataExporter implements Exporter
{
    const VARIABLE = '$column';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0)
    {
        /** @var ColumnMetadata $value */
        $variableExporter = new VariableExporter();
        $indentation      = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference  = $indentation . static::VARIABLE;
        $lines            = [];

        $lines[] = $objectReference . ' = ' . $this->exportInstantiation($value);

        if (! empty($value->getColumnDefinition())) {
            $lines[] = $objectReference. '->setColumnDefinition("' . $value->getColumnDefinition() . '");';
        }

        $lines[] = $objectReference . '->setTableName("' . $value->getTableName() . '");';
        $lines[] = $objectReference . '->setLength(' . $value->getLength() . ');';
        $lines[] = $objectReference . '->setScale(' . $value->getScale() . ');';
        $lines[] = $objectReference . '->setPrecision(' . $value->getPrecision() . ');';
        $lines[] = $objectReference . '->setOptions(' . ltrim($variableExporter->export($value->getOptions(), $indentationLevel + 1)) . ');';
        $lines[] = $objectReference . '->setPrimaryKey(' . ltrim($variableExporter->export($value->isPrimaryKey(), $indentationLevel + 1)) . ');';
        $lines[] = $objectReference . '->setNullable(' . ltrim($variableExporter->export($value->isNullable(), $indentationLevel + 1)) . ');';
        $lines[] = $objectReference . '->setUnique(' . ltrim($variableExporter->export($value->isUnique(), $indentationLevel + 1)) . ');';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param ColumnMetadata $metadata
     *
     * @return string
     */
    protected function exportInstantiation(ColumnMetadata $metadata)
    {
        return sprintf(
            'new Mapping\ColumnMetadata("%s", Type::getType("%s"));',
            $metadata->getColumnName(),
            $metadata->getTypeName()
        );
    }
}