<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\AssociationMetadata;
use const PHP_EOL;
use function array_diff;
use function implode;
use function str_repeat;
use function strtoupper;

abstract class AssociationMetadataExporter implements Exporter
{
    public const VARIABLE = '$property';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
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
            $lines[] = $objectReference . '->setOwningSide(false);';
        }

        if (! empty($value->getInversedBy())) {
            $lines[] = $objectReference . '->setInversedBy("' . $value->getInversedBy() . '");';
        }

        $lines[] = $objectReference . '->setSourceEntity("' . $value->getSourceEntity() . '");';
        $lines[] = $objectReference . '->setTargetEntity("' . $value->getTargetEntity() . '");';
        $lines[] = $objectReference . '->setFetchMode(Mapping\FetchMode::' . strtoupper($value->getFetchMode()) . '");';
        $lines[] = $objectReference . '->setCascade(' . $variableExporter->export($cascade, $indentationLevel + 1) . ');';
        $lines[] = $objectReference . '->setOrphanRemoval(' . $variableExporter->export($value->isOrphanRemoval(), $indentationLevel + 1) . ');';
        $lines[] = $objectReference . '->setPrimaryKey(' . $variableExporter->export($value->isPrimaryKey(), $indentationLevel + 1) . ');';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param string[] $cascade
     *
     * @return string[]
     */
    private function resolveCascade(array $cascade) : array
    {
        return array_diff(['remove', 'persist', 'refresh'], $cascade)
            ? $cascade
            : ['all'];
    }

    abstract protected function exportInstantiation(AssociationMetadata $metadata) : string;
}
