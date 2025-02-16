<?php

declare(strict_types=1);

namespace Doctrine\StaticAnalysis\Tools\Pagination;

use Doctrine\ORM\Tools\Pagination\Paginator;

/** @template-covariant T of object */
abstract class PaginatorFactory
{
    /** @var class-string<T> */
    private $class;

    /** @param class-string<T> $class */
    final public function __construct(string $class)
    {
        $this->class = $class;
    }

    /** @return class-string<T> */
    public function getClass(): string
    {
        return $this->class;
    }

    /** @phpstan-return Paginator<T> */
    abstract public function createPaginator(): Paginator;
}

interface Animal
{
}

class Cat implements Animal
{
}

/** @param Paginator<Animal> $paginator */
function getFirstAnimal(Paginator $paginator): Animal|null
{
    foreach ($paginator as $result) {
        return $result;
    }

    return null;
}

/** @param PaginatorFactory<Cat> $catPaginatorFactory */
function test(PaginatorFactory $catPaginatorFactory): Animal|null
{
    return getFirstAnimal($catPaginatorFactory->createPaginator());
}
