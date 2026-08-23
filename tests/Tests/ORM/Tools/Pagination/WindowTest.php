<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Tools\Pagination\Window;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WindowTest extends TestCase
{
    public function testWindowIsFinal(): void
    {
        self::assertTrue((new ReflectionClass(Window::class))->isFinal());
    }

    public function testGetters(): void
    {
        $window = new Window(50, 25);

        self::assertSame(50, $window->getFirstResult());
        self::assertSame(25, $window->getMaxResults());
    }

    public function testNext(): void
    {
        $next = (new Window(0, 25))->next();

        self::assertSame(25, $next->getFirstResult());
        self::assertSame(25, $next->getMaxResults());
    }

    public function testPrevious(): void
    {
        $previous = (new Window(50, 25))->previous();

        self::assertSame(25, $previous->getFirstResult());
        self::assertSame(25, $previous->getMaxResults());
    }

    public function testPreviousIsClampedAtZero(): void
    {
        $previous = (new Window(10, 25))->previous();

        self::assertSame(0, $previous->getFirstResult());
    }

    public function testFromPageNumberAndSize(): void
    {
        $window = Window::fromPageNumberAndSize(3, 25);

        self::assertSame(50, $window->getFirstResult());
        self::assertSame(25, $window->getMaxResults());
        self::assertSame(3, $window->getPageNumber());
    }

    public function testGetPageNumber(): void
    {
        self::assertSame(1, (new Window(0, 25))->getPageNumber());
        self::assertSame(2, (new Window(25, 25))->getPageNumber());
        self::assertSame(3, (new Window(50, 25))->getPageNumber());
    }

    public function testNegativeFirstResultThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Window(-1, 20);
    }

    public function testZeroMaxResultsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Window(0, 0);
    }

    public function testFromPageNumberAndSizeWithPageNumberBelowOneThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Window::fromPageNumberAndSize(0, 25);
    }

    public function testIsImmutable(): void
    {
        $window = new Window(0, 25);
        $window->next();

        self::assertSame(0, $window->getFirstResult());
    }
}
