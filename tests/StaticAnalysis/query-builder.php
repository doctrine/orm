<?php

declare(strict_types=1);

namespace Doctrine\ORM;

class Cat
{
}

/** @extends EntityRepository<Cat> */
class CatRepository extends EntityRepository
{
}

/** @return array<array-key, Cat> */
function getResultAsEntities(CatRepository $catRepository): array
{
    return $catRepository->createQueryBuilder('c')->getQuery()->getResult();
}

function getOneOrNullEntity(CatRepository $catRepository): Cat|null
{
    return $catRepository->createQueryBuilder('c')->getQuery()->getOneOrNullResult();
}

function getSingleEntity(CatRepository $catRepository): Cat
{
    return $catRepository->createQueryBuilder('c')->getQuery()->getSingleResult();
}

/**
 * Once QueryBuilder::select is called, all results will be mixed. User must manually assert returned type.
 *
 * @see QueryBuilder::select()
 */
function getMixedResults(CatRepository $catRepository): mixed
{
    return $catRepository->createQueryBuilder('c')
        ->select('c.id')
        ->getQuery()->getResult();
}

function getMixedOrNullResult(CatRepository $catRepository): mixed
{
    return $catRepository->createQueryBuilder('c')
        ->select('c.id')
        ->getQuery()->getOneOrNullResult();
}

function getMixedResult(CatRepository $catRepository): mixed
{
    return $catRepository->createQueryBuilder('c')
        ->select('c.id')
        ->getQuery()->getSingleResult();
}
