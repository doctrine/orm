<?php
declare(strict_types=1);

namespace Doctrine\Tests;

use function set_error_handler;
use const E_USER_DEPRECATED;

trait VerifyDeprecations
{
    /** @var string[] */
    private $expectedDeprecations = [];

    /** @var string[] */
    private $actualDeprecations = [];

    /** @var callable|null */
    private $originalHandler;

    /** @before */
    public function resetDeprecations() : void
    {
        $this->actualDeprecations   = [];
        $this->expectedDeprecations = [];

        $this->originalHandler = set_error_handler(
            function (int $errorNumber, string $errorMessage) : void {
                $this->actualDeprecations[] = $errorMessage;
            },
            E_USER_DEPRECATED
        );
    }

    /** @after */
    public function resetErrorHandler() : void
    {
        set_error_handler($this->originalHandler, E_USER_DEPRECATED);
        $this->originalHandler = null;
    }

    /** @after */
    public function validateDeprecationExpectations() : void
    {
        if ($this->expectedDeprecations === []) {
            return;
        }

        self::assertSame(
            $this->expectedDeprecations,
            $this->actualDeprecations,
            'Triggered deprecation messages do not match with expected ones.'
        );
    }

    protected function expectDeprecationMessage(string $message) : void
    {
        $this->expectedDeprecations[] = $message;
    }

    protected function assertHasDeprecationMessages() : void
    {
        self::assertNotSame([], $this->actualDeprecations, 'Failed asserting that test has triggered deprecation messages.');
    }

    protected function assertNotHasDeprecationMessages() : void
    {
        self::assertSame([], $this->actualDeprecations, 'Failed asserting that test has not triggered deprecation messages.');
    }
}
