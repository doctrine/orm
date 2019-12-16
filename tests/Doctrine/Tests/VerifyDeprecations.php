<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use const E_USER_DEPRECATED;
use function in_array;
use function set_error_handler;

trait VerifyDeprecations
{
    /** @var string[] */
    private $expectedDeprecations = [];

    /** @var string[] */
    private $actualDeprecations = [];

    /** @var string[] */
    private $ignoredDeprecations = [];

    /** @var callable|null */
    private $originalHandler;

    /** @before */
    public function resetDeprecations() : void
    {
        $this->actualDeprecations   = [];
        $this->expectedDeprecations = [];
        $this->ignoredDeprecations  = [];

        $this->originalHandler = set_error_handler(
            function (int $errorNumber, string $errorMessage) : void {
                if (in_array($errorMessage, $this->ignoredDeprecations, true)) {
                    return;
                }

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

    protected function ignoreDeprecationMessage(string $message) : void
    {
        $this->ignoredDeprecations[] = $message;
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
