<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\CacheMetadata;

class CacheMetadataExporter implements Exporter
{
    const VARIABLE = '$cache';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var CacheMetadata $value */
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;
        $lines           = [];

        $lines[] = $objectReference . ' = ' . $this->exportInstantiation($value);

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param CacheMetadata $metadata
     *
     * @return string
     */
    protected function exportInstantiation(CacheMetadata $metadata) : string
    {
        return sprintf(
            'new Mapping\CacheMetadata(Mapping\CacheUsage::%s, "%s");',
            strtoupper($metadata->getUsage()),
            $metadata->getRegion()
        );
    }
}
