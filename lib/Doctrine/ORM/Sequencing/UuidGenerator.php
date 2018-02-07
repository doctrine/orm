<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Represents an ID generator that uses the database UUID expression
 */
class UuidGenerator implements Generator
{
    /**
     * {@inheritdoc}
     */
    public function generate(EntityManagerInterface $em, $entity)
    {
        $conn = $em->getConnection();
        $sql  = 'SELECT ' . $conn->getDatabasePlatform()->getGuidExpression();

        return $conn->query($sql)->fetchColumn(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isPostInsertGenerator()
    {
        return false;
    }
}
