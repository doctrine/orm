<?php

declare(strict_types=1);

namespace Doctrine\Tests;

trait VerifyDeprecations
{
    /** @before */
    public function resetDeprecations(): void
    {
    }

    /** @after */
    public function resetErrorHandler(): void
    {
    }

    /** @after */
    public function validateDeprecationExpectations(): void
    {
    }

    protected function ignoreDeprecationMessage(string $message): void
    {
    }

    protected function expectDeprecationMessageSame(string $message): void
    {
    }

    protected function assertHasDeprecationMessages(): void
    {
    }

    protected function assertNotHasDeprecationMessages(): void
    {
    }
}
