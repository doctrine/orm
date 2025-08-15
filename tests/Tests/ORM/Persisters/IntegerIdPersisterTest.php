<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type as DbalType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\WrappedIntegerPrimaryKey\Category;
use Doctrine\Tests\Models\WrappedIntegerPrimaryKey\IntegerIdType;
use Doctrine\Tests\OrmTestCase;

final class IntegerIdPersisterTest extends OrmTestCase
{
    private EntityManager|null $entityManager = null;

    public function testEagerFetchMode(): void
    {
        $entityManager = $this->createEntityManager();

        $this->createDummyBlogData($entityManager, 1, 1);

        /** @var Category[] $topCategories */
        $topCategories = $entityManager->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->andWhere('category.parent IS NULL')
            ->getQuery()
            ->setFetchMode(Category::class, 'children', ClassMetadata::FETCH_EAGER)
            ->getResult();

        self::assertCount(1, $topCategories);

        foreach ($topCategories as $topCategory) {
            // the real count of children in db is 1
            self::assertCount(
                1,
                $entityManager->createQueryBuilder()
                    ->select('category')
                    ->from(Category::class, 'category')
                    ->andWhere('category.parent = :parent')
                    ->setParameter('parent', $topCategory->getId()->getValue())
                    ->getQuery()
                    ->getResult(),
            );

            // our collection is initialized
            self::assertTrue($topCategory->getChildren()->isInitialized());

            // but fails to load the children
            self::assertSame(1, $topCategory->getChildren()->count());
        }
    }

    private function createDummyBlogData(
        EntityManager $entityManager,
        int $categoryCount = 1,
        int $categoryParentsCount = 0,
    ): void {
        for ($h = 0; $h < $categoryCount; $h++) {
            $categoryParent = null;

            for ($i = 0; $i < $categoryParentsCount; $i++) {
                $categoryParent = new Category('CategoryParent#' . $i, $categoryParent);
                $entityManager->persist($categoryParent);
            }

            $category = new Category('Category#' . $h, $categoryParent);
            $entityManager->persist($category);
        }

        $entityManager->flush();
        $entityManager->clear();
    }

    private function createEntityManager(): EntityManager
    {
        if ($this->entityManager !== null) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../../Models/WrappedIntegerPrimaryKey'], isDevMode: true);

        if (! DbalType::hasType(IntegerIdType::NAME)) {
            DbalType::addType(IntegerIdType::NAME, IntegerIdType::class);
        }

        $connection    = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManagerMock($connection, $config);

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $schemaValidator = new SchemaValidator($entityManager);
        $schemaValidator->validateMapping();

        $this->entityManager = $entityManager;

        return $entityManager;
    }
}
