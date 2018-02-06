<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\TransientMetadata;
use function sprintf;
use function str_repeat;

class TransientMetadataExporter implements Exporter
{
    public const VARIABLE = '$property';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var TransientMetadata $value */
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;

        return $objectReference . ' = ' . $this->exportInstantiation($value);
    }

    protected function exportInstantiation(TransientMetadata $metadata) : string
    {
        return sprintf(
            'new Mapping\TransientMetadata("%s");',
            $metadata->getName()
        );
    }
}
