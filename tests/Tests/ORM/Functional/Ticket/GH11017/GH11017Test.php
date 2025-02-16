<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11017;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\OrmFunctionalTestCase;

use function sprintf;

class GH11017Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11017Entity::class,
        ]);
    }

    public function testPostPersistListenerUpdatingObjectFieldWhileOtherInsertPending(): void
    {
        $entity1        = new GH11017Entity();
        $entity1->field = GH11017Enum::FIRST;
        $this->_em->persist($entity1);

        $this->_em->flush();
        $this->_em->clear();

        $rsm = new ResultSetMappingBuilder($this->_em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata(GH11017Entity::class, 'e');

        $tableName = $this->_em->getClassMetadata(GH11017Entity::class)->getTableName();
        $sql       = sprintf('SELECT %s FROM %s e WHERE id = :id', $rsm->generateSelectClause(), $tableName);

        $query = $this->_em->createNativeQuery($sql, $rsm)
            ->setParameter('id', $entity1->id);

        $entity1Reloaded = $query->getSingleResult(AbstractQuery::HYDRATE_ARRAY);
        self::assertNotNull($entity1Reloaded);
        self::assertSame($entity1->field, $entity1Reloaded['field']);
    }
}
