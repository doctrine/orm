<?php

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
        $this->column    = $column;
        $this->generator = $generator;
    }

    /**
     * @return mixed[]
     */
    public function execute(EntityManagerInterface $entityManager, object $entity) : array
    {
        $value = $this->generator->generate($entityManager, $entity);

        $platform       = $entityManager->getConnection()->getDatabasePlatform();
        $convertedValue = $this->column->getType()->convertToPHPValue($value, $platform);

        return [$this->column->getColumnName() => $convertedValue];
    }

    public function isDeferred() : bool
    {
        return $this->generator->isPostInsertGenerator();
    }
}
