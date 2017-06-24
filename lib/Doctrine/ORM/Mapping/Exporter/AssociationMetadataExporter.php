<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

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
        $resolvedCascade = ['remove', 'persist', 'refresh'];

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
