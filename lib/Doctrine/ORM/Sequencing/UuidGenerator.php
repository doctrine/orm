<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing;

use Doctrine\ORM\EntityManager;

/**
 * Represents an ID generator that uses the database UUID expression
 *
 * @since 2.3
 * @author Maarten de Keizer <m.de.keizer@markei.nl>
 */
class UuidGenerator implements Generator
{
    /**
     * {@inheritdoc}
     */
    public function generate(EntityManager $em, $entity)
    {
        $conn = $em->getConnection();
        $sql = 'SELECT ' . $conn->getDatabasePlatform()->getGuidExpression();

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
