<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\StrictLoading;

use Doctrine\ORM\Exception\StrictLoadingViolation;
use Doctrine\ORM\StrictLoading\LazyLoad;
use Doctrine\ORM\StrictLoading\LazyLoadKind;
use Doctrine\ORM\StrictLoading\StrictLoading;
use Doctrine\ORM\StrictLoading\StrictLoadingMode;
use Doctrine\ORM\StrictLoading\StrictLoadingViolationHandler;
use Doctrine\Tests\Models\CMS\CmsUser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StrictLoadingTest extends TestCase
{
    public function testDisabledByDefault(): void
    {
        $strictLoading = new StrictLoading();

        self::assertSame(StrictLoadingMode::Disabled, $strictLoading->getMode());
        self::assertFalse($strictLoading->isActive());

        $strictLoading->check(LazyLoad::entity(CmsUser::class, ['id' => 1]));
    }

    public function testAllModeReportsEveryLazyLoad(): void
    {
        $handler       = new CollectViolations();
        $strictLoading = new StrictLoading(StrictLoadingMode::All, $handler);

        $strictLoading->check(LazyLoad::entity(CmsUser::class, ['id' => 1]));
        $strictLoading->check(LazyLoad::entity(CmsUser::class, ['id' => 2]));
        $strictLoading->check(LazyLoad::collection(CmsUser::class, 'articles'));

        self::assertCount(3, $handler->violations);
    }

    public function testNPlusOneOnlyModeAllowsTheFirstLoadOfEachShape(): void
    {
        $handler       = new CollectViolations();
        $strictLoading = new StrictLoading(StrictLoadingMode::NPlusOneOnly, $handler);

        // First load of each shape is fine: it does not repeat.
        $strictLoading->check(LazyLoad::entity(CmsUser::class, ['id' => 1]));
        $strictLoading->check(LazyLoad::collection(CmsUser::class, 'articles'));
        self::assertCount(0, $handler->violations);

        // Loading the same shape again is an N+1 query.
        $strictLoading->check(LazyLoad::entity(CmsUser::class, ['id' => 2]));
        $strictLoading->check(LazyLoad::collection(CmsUser::class, 'articles'));

        self::assertCount(2, $handler->violations);
        self::assertSame(LazyLoadKind::Entity, $handler->violations[0]->kind);
        self::assertSame(LazyLoadKind::Collection, $handler->violations[1]->kind);
    }

    public function testResetStartsANewNPlusOneScope(): void
    {
        $handler       = new CollectViolations();
        $strictLoading = new StrictLoading(StrictLoadingMode::NPlusOneOnly, $handler);

        $strictLoading->check(LazyLoad::entity(CmsUser::class, ['id' => 1]));
        $strictLoading->reset();
        $strictLoading->check(LazyLoad::entity(CmsUser::class, ['id' => 2]));

        self::assertCount(0, $handler->violations);
    }

    public function testAllowSuspendsStrictLoading(): void
    {
        $handler       = new CollectViolations();
        $strictLoading = new StrictLoading(StrictLoadingMode::All, $handler);

        $returned = $strictLoading->allow(static function () use ($strictLoading, $handler): string {
            self::assertFalse($strictLoading->isActive());
            $strictLoading->check(LazyLoad::collection(CmsUser::class, 'articles'));
            self::assertCount(0, $handler->violations);

            return 'from callback';
        });

        self::assertSame('from callback', $returned);
        self::assertTrue($strictLoading->isActive());
    }

    public function testAllowIsReentrant(): void
    {
        $strictLoading = new StrictLoading(StrictLoadingMode::All);

        $strictLoading->allow(static function () use ($strictLoading): void {
            $strictLoading->allow(static function () use ($strictLoading): void {
                self::assertFalse($strictLoading->isActive());
            });

            self::assertFalse($strictLoading->isActive());
        });

        self::assertTrue($strictLoading->isActive());
    }

    public function testStrictLoadingIsRestoredWhenTheCallbackThrows(): void
    {
        $strictLoading = new StrictLoading(StrictLoadingMode::All);

        try {
            $strictLoading->allow(static function (): void {
                throw new RuntimeExceptionStub();
            });
        } catch (RuntimeExceptionStub) {
        }

        self::assertTrue($strictLoading->isActive());
    }

    public function testDefaultHandlerThrows(): void
    {
        $strictLoading = new StrictLoading(StrictLoadingMode::All);

        $this->expectException(StrictLoadingViolation::class);
        $this->expectExceptionMessage('lazily loading collection ' . CmsUser::class . '#articles is not allowed here.');

        $strictLoading->check(LazyLoad::collection(CmsUser::class, 'articles'));
    }

    public function testViolationExceptionCarriesTheLazyLoad(): void
    {
        $strictLoading = new StrictLoading(StrictLoadingMode::All);
        $lazyLoad      = LazyLoad::entity(CmsUser::class, ['id' => 42]);

        try {
            $strictLoading->check($lazyLoad);
            self::fail('Expected a strict loading violation.');
        } catch (StrictLoadingViolation $exception) {
            self::assertSame($lazyLoad, $exception->lazyLoad);
            self::assertStringContainsString('entity ' . CmsUser::class . '(42)', $exception->getMessage());
        }
    }

    public function testViolationHandlerMayNotTriggerNestedViolations(): void
    {
        $strictLoading = new StrictLoading(StrictLoadingMode::All);
        $handler       = new class ($strictLoading) implements StrictLoadingViolationHandler {
            public int $calls = 0;

            public function __construct(private StrictLoading $strictLoading)
            {
            }

            public function violation(LazyLoad $lazyLoad, StrictLoadingMode $mode): void
            {
                ++$this->calls;

                // A handler that renders the violation may itself touch lazy data.
                $this->strictLoading->check(LazyLoad::collection(CmsUser::class, 'articles'));
            }
        };

        $strictLoading->setViolationHandler($handler);
        $strictLoading->check(LazyLoad::collection(CmsUser::class, 'articles'));

        self::assertSame(1, $handler->calls);
    }

    public function testDescriptions(): void
    {
        self::assertSame(
            'entity ' . CmsUser::class . '(1)',
            LazyLoad::entity(CmsUser::class, ['id' => 1])->describe(),
        );
        self::assertSame(
            'entity ' . CmsUser::class . '(id: 1, name: foo)',
            LazyLoad::entity(CmsUser::class, ['id' => 1, 'name' => 'foo'])->describe(),
        );
        self::assertSame(
            'collection ' . CmsUser::class . '#articles',
            LazyLoad::collection(CmsUser::class, 'articles')->describe(),
        );
        self::assertSame(
            'count() on collection ' . CmsUser::class . '#articles',
            LazyLoad::collectionQuery(CmsUser::class, 'articles', 'count')->describe(),
        );
    }

    public function testSignatureIgnoresIdentifierValues(): void
    {
        self::assertSame(
            LazyLoad::entity(CmsUser::class, ['id' => 1])->signature(),
            LazyLoad::entity(CmsUser::class, ['id' => 2])->signature(),
        );
        self::assertNotSame(
            LazyLoad::collection(CmsUser::class, 'articles')->signature(),
            LazyLoad::collection(CmsUser::class, 'phonenumbers')->signature(),
        );
        self::assertNotSame(
            LazyLoad::collection(CmsUser::class, 'articles')->signature(),
            LazyLoad::collectionQuery(CmsUser::class, 'articles', 'count')->signature(),
        );
    }

    public function testResumeWithoutSuspendIsHarmless(): void
    {
        $strictLoading = new StrictLoading(StrictLoadingMode::All);
        $strictLoading->resume();

        self::assertTrue($strictLoading->isActive());
    }
}

final class CollectViolations implements StrictLoadingViolationHandler
{
    /** @var list<LazyLoad> */
    public array $violations = [];

    /** @var list<StrictLoadingMode> */
    public array $modes = [];

    public function violation(LazyLoad $lazyLoad, StrictLoadingMode $mode): void
    {
        $this->violations[] = $lazyLoad;
        $this->modes[]      = $mode;
    }
}

final class RuntimeExceptionStub extends RuntimeException
{
}
